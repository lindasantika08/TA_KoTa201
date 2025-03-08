<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\AnswersPeer;
use App\Models\Assessment;
use Illuminate\Support\Facades\DB;

class ProcessFlaskPeerAssessment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $answerData;
    protected $answerId;

    // Add retry configuration
    public $tries = 3;
    public $backoff = 60;

    // Add timeout configuration
    public $timeout = 180;

    /**
     * Create a new job instance.
     *
     * @param array $answerData
     * @param string $answerId
     * @return void
     */
    public function __construct($answerData, $answerId)
    {
        $this->answerData = $answerData;
        $this->answerId = $answerId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            // Start with detailed logging
            Log::info('Starting ProcessFlaskPeerAssessment job', [
                'peer_answer_id' => $this->answerId,
                'data' => $this->answerData
            ]);

            // First, try to directly load the record by ID (most reliable method)
            // This avoids composite key issues
            $answerRecord = AnswersPeer::find($this->answerId);

            if (!$answerRecord) {
                Log::warning('Answer not found by ID, trying to find by composite keys', [
                    'peer_answer_id' => $this->answerId
                ]);

                // As a fallback, try to find by composite keys
                if (
                    isset($this->answerData['mahasiswa_id']) &&
                    isset($this->answerData['peer_id']) &&
                    isset($this->answerData['question_id'])
                ) {

                    $answerRecord = AnswersPeer::where([
                        'mahasiswa_id' => $this->answerData['mahasiswa_id'],
                        'peer_id' => $this->answerData['peer_id'],
                        'question_id' => $this->answerData['question_id']
                    ])->first();

                    if (!$answerRecord) {
                        Log::error('Answer not found by composite keys either, aborting job', [
                            'mahasiswa_id' => $this->answerData['mahasiswa_id'] ?? null,
                            'peer_id' => $this->answerData['peer_id'] ?? null,
                            'question_id' => $this->answerData['question_id'] ?? null
                        ]);
                        return;
                    }
                } else {
                    Log::error('Required fields missing in answer data and record not found by ID', [
                        'answer_data' => $this->answerData
                    ]);
                    return;
                }
            }

            // Get the answer text and score directly from the current record if not provided in job data
            $answerText = $this->answerData['answer'] ?? $answerRecord->answer;
            $scoreGiven = $this->answerData['score_given'] ?? $this->answerData['score'] ?? $answerRecord->score;

            // Normalize score to 1-5 range if it's currently 0-100
            $normalizedScore = (int)$scoreGiven;
            if ($normalizedScore > 5) {
                $normalizedScore = min(5, max(1, ceil($normalizedScore / 20)));
            }

            // Get criteria information
            $question = Assessment::with('typeCriteria')->where('id', $answerRecord->question_id)->first();

            // Create criteria dictionary
            $criteriaDict = [];
            if ($question && $question->typeCriteria) {
                for ($i = 1; $i <= 5; $i++) {
                    $bobotField = "bobot_" . $i;
                    $criteriaDict[$i] = $question->typeCriteria->$bobotField ?? "No criteria defined for score $i";
                }
            } else {
                $criteriaDict = [
                    1 => "Did not meet any requirements",
                    2 => "Met few requirements",
                    3 => "Met some requirements",
                    4 => "Met most requirements",
                    5 => "Met all requirements"
                ];
            }

            // Check if we have criteria from the job data
            if (isset($this->answerData['criteria']) && is_array($this->answerData['criteria'])) {
                $criteriaDict = $this->answerData['criteria'];
            }

            // Prepare data for Flask service
            $flaskData = [
                'answer' => $answerText,
                'score_given' => $normalizedScore,
                'criteria' => $criteriaDict
            ];

            // Log data being sent to Flask
            Log::info('Sending data to Flask service', [
                'peer_answer_id' => $this->answerId,
                'flask_data' => $flaskData
            ]);

            // Send to Flask service
            $flaskUrl = env('FLASK_SERVICE_URL', 'http://localhost:5000/assess');

            $flaskResponse = Http::timeout(120)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ])
                ->post($flaskUrl, $flaskData);

            if ($flaskResponse->successful()) {
                $flaskResult = $flaskResponse->json();

                // Log successful response
                Log::info('Received successful response from Flask', [
                    'peer_answer_id' => $this->answerId,
                    'flask_result' => $flaskResult
                ]);

                // Verify the structure of response
                if (!isset($flaskResult['best_score']) || !isset($flaskResult['best_similarity'])) {
                    Log::error('Flask response missing required fields', [
                        'response' => $flaskResult
                    ]);
                    return;
                }

                // Cast values according to your model's casts
                $bestScore = isset($flaskResult['best_score']) ? (int)$flaskResult['best_score'] : null;
                $bestSimilarity = isset($flaskResult['best_similarity']) ? (float)$flaskResult['best_similarity'] : null;

                // Direct update using query builder first (most reliable)
                try {
                    $updated = DB::table('answers_peer')
                        ->where('id', $answerRecord->id)
                        ->update([
                            'score_SLA' => $bestScore,
                            'similarity' => $bestSimilarity,
                            'updated_at' => now()
                        ]);

                    if ($updated) {
                        Log::info('Successfully updated peer answer with Flask result', [
                            'peer_answer_id' => $this->answerId,
                            'flask_result' => [
                                'best_score' => $bestScore,
                                'best_similarity' => $bestSimilarity
                            ]
                        ]);
                        return;
                    }
                } catch (\Exception $e) {
                    Log::error('Error during direct update', [
                        'error' => $e->getMessage(),
                        'peer_answer_id' => $this->answerId
                    ]);
                }

                // If direct update failed, try using the model
                try {
                    // Force reload the model to avoid stale data
                    $freshRecord = AnswersPeer::find($answerRecord->id);

                    if ($freshRecord) {
                        $freshRecord->score_SLA = $bestScore;
                        $freshRecord->similarity = $bestSimilarity;
                        $freshRecord->save();

                        Log::info('Successfully updated peer answer using Eloquent model', [
                            'peer_answer_id' => $this->answerId
                        ]);
                        return;
                    }
                } catch (\Exception $e) {
                    Log::error('Error during Eloquent update', [
                        'error' => $e->getMessage(),
                        'peer_answer_id' => $this->answerId
                    ]);
                }

                // Last resort: raw SQL update
                try {
                    DB::statement(
                        "UPDATE answers_peer SET 
                            score_SLA = ?, 
                            similarity = ?, 
                            updated_at = NOW() 
                        WHERE id = ?",
                        [$bestScore, $bestSimilarity, $answerRecord->id]
                    );

                    Log::info('Successfully updated peer answer using raw SQL', [
                        'peer_answer_id' => $this->answerId
                    ]);
                    return;
                } catch (\Exception $e) {
                    Log::error('Error during raw SQL update', [
                        'error' => $e->getMessage(),
                        'peer_answer_id' => $this->answerId
                    ]);
                }

                // All update methods failed
                Log::error('All update attempts failed for peer answer', [
                    'peer_answer_id' => $this->answerId
                ]);

                // Retry if we haven't hit the limit
                if ($this->attempts() < $this->tries) {
                    $this->release($this->backoff);
                }
            } else {
                Log::error('Flask service returned error', [
                    'status' => $flaskResponse->status(),
                    'body' => $flaskResponse->body(),
                    'peer_answer_id' => $this->answerId
                ]);

                // Retry if we haven't hit the limit
                if ($this->attempts() < $this->tries) {
                    $this->release($this->backoff);
                }
            }
        } catch (\Exception $e) {
            Log::error('Exception in ProcessFlaskPeerAssessment job', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'peer_answer_id' => $this->answerId
            ]);

            // Retry if we haven't hit the limit
            if ($this->attempts() < $this->tries) {
                $this->release($this->backoff);
            }
        }
    }

    /**
     * The job failed to process.
     *
     * @param \Exception $exception
     * @return void
     */
    public function failed(\Exception $exception)
    {
        Log::error('Flask peer assessment job failed permanently', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'peer_answer_id' => $this->answerId,
            'attempts' => $this->attempts()
        ]);
    }
}
