<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Answers;

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
            // Get the answer record to access the question_id
            $answer = Answers::find($this->answerId);

            if (!$answer) {
                Log::error('Answer not found in job', [
                    'answer_id' => $this->answerId
                ]);
                return;
            }

            // Prepare data for Flask - only send question_id, score, and answer
            $flaskData = [
                'question_id' => $answer->question_id, // Pass the actual question_id from the answer record
                'answer' => $this->answerData['answer'],
                'score' => $this->answerData['score']
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
                $answer->update([
                    'score_SLA' => $flaskResult['best_score'] ?? null,
                    'similarity' => $flaskResult['best_similarity'] ?? null
                ]);

                Log::info('Successfully updated answer with Flask result in job', [
                    'answer_id' => $this->answerId,
                    'flask_result' => $flaskResult
                ]);
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
