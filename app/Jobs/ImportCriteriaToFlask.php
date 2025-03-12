<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Assessment;
use App\Models\TypeCriteria;

class ImportCriteriaToFlask implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $assessmentId;
    protected $flaskUrl;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * Create a new job instance.
     *
     * @param string $assessmentId The UUID of the assessment to import
     * @return void
     */
    public function __construct($assessmentId)
    {
        $this->assessmentId = $assessmentId;
        $this->flaskUrl = env('FLASK_APP_URL', 'http://localhost:5000');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            // Get Assessment based on the assessment ID
            $assessment = Assessment::with('typeCriteria')->find($this->assessmentId);

            if (!$assessment) {
                Log::error("ImportCriteriaToFlask: Assessment not found for ID: {$this->assessmentId}");
                return;
            }

            // Get criteria from the related TypeCriteria
            $criteria = $assessment->typeCriteria;

            if (!$criteria) {
                Log::error("ImportCriteriaToFlask: No criteria found for assessment ID: {$this->assessmentId}");
                return;
            }

            // Prepare data for the Flask API
            $data = [
                'assessment_id' => $this->assessmentId,
                'rubrik_id' => $assessment->criteria_id,
                'question' => $assessment->question,
                'bobot_1' => $criteria->bobot_1 ?? '',
                'bobot_2' => $criteria->bobot_2 ?? '',
                'bobot_3' => $criteria->bobot_3 ?? '',
                'bobot_4' => $criteria->bobot_4 ?? '',
                'bobot_5' => $criteria->bobot_5 ?? '',
                'aspect' => $criteria->aspect ?? '',
                'criteria' => $criteria->criteria ?? '',
                'skill_type' => $assessment->skill_type ?? '',
                'type' => $assessment->type ?? '',
            ];

            Log::info("Sending assessment criteria to Flask: " . json_encode($data));

            // Make HTTP POST request to Flask
            $response = Http::timeout(30)->post("{$this->flaskUrl}/import-criteria", $data);

            if ($response->successful()) {
                $responseData = $response->json();
                Log::info("Successfully imported criteria to Flask. Response: " . json_encode($responseData));

                // Here you could update a status field in your database to indicate the criteria
                // has been successfully sent to Flask if needed
            } else {
                Log::error("Failed to import criteria to Flask. Status: {$response->status()}, Response: " . $response->body());

                // If needed, you could handle specific error codes here
                if ($response->status() >= 500) {
                    // Server error, maybe retry
                    $this->release(30); // Retry after 30 seconds
                }
            }
        } catch (\Exception $e) {
            Log::error("Exception in ImportCriteriaToFlask job: " . $e->getMessage());

            // Determine if we should retry based on the exception
            $shouldRetry = $this->shouldRetry($e);

            if ($shouldRetry && $this->attempts() < $this->tries) {
                $this->release(60 * $this->attempts()); // Exponential backoff
            } else {
                // Log as a critical error if we've exhausted retries
                Log::critical("Failed to import criteria after {$this->attempts()} attempts: {$this->assessmentId}");

                // You could notify administrators here or update a status in the database
            }

            throw $e; // Re-throw to mark job as failed
        }
    }

    /**
     * Determine if the job should be retried based on the exception
     *
     * @param \Exception $e
     * @return bool
     */
    private function shouldRetry(\Exception $e)
    {
        // Retry for network-related exceptions
        if ($e instanceof \Illuminate\Http\Client\ConnectionException) {
            return true;
        }

        // Add other exceptions that should trigger a retry
        return false;
    }
}
