<?php
// Pertama, buat Job baru: app/Jobs/ProcessFlaskAssessment.php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Answers;
use App\Models\Assessment;

class ProcessFlaskAssessment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $answerData;
    protected $answerId;

    /**
     * Create a new job instance.
     *
     * @param array $answerData
     * @param int $answerId
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
            // Get the question to access its criteria
            $question = Assessment::with('typeCriteria')->where('id', $this->answerData['question_id'])->first();

            if (!$question) {
                Log::error('Question not found in background job', [
                    'question_id' => $this->answerData['question_id'],
                    'answer_id' => $this->answerId
                ]);
                return;
            }

            // Get criteria information
            $typeCriteria = $question->typeCriteria;

            // Create criteria dictionary
            $criteriaDict = [];
            if ($typeCriteria) {
                for ($i = 1; $i <= 5; $i++) {
                    $bobotField = "bobot_" . $i;
                    $criteriaDict[$i] = isset($typeCriteria->$bobotField) && !empty($typeCriteria->$bobotField)
                        ? $typeCriteria->$bobotField
                        : "No criteria defined for score $i";
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

            // Prepare data for Flask service
            $flaskData = [
                'answer' => $this->answerData['answer'],
                'score_given' => $this->answerData['score'],
                'criteria' => $criteriaDict
            ];

            // Log data yang akan dikirim ke Flask
            Log::info('Processing Flask assessment in job queue:', [
                'answer_id' => $this->answerId,
                'flask_data' => $flaskData
            ]);

            // Send to Flask service
            $flaskUrl = env('FLASK_SERVICE_URL', 'http://localhost:5000/assess');
            $flaskResponse = Http::timeout(120)->withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ])->post($flaskUrl, $flaskData);

            if ($flaskResponse->successful()) {
                $flaskResult = $flaskResponse->json();

                // Update jawaban dengan hasil dari Flask
                $answer = Answers::find($this->answerId);
                if ($answer) {
                    $answer->update([
                        'score_SLA' => $flaskResult['best_score'] ?? null,
                        'similarity' => $flaskResult['best_similarity'] ?? null
                    ]);

                    Log::info('Successfully updated answer with Flask result in job', [
                        'answer_id' => $this->answerId,
                        'flask_result' => $flaskResult
                    ]);
                }
            } else {
                Log::error('Flask service returned error in job', [
                    'status' => $flaskResponse->status(),
                    'response' => $flaskResponse->body(),
                    'answer_id' => $this->answerId
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error in Flask assessment job', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'answer_id' => $this->answerId
            ]);
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
        Log::error('Flask assessment job failed', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'answer_id' => $this->answerId
        ]);
    }
}
