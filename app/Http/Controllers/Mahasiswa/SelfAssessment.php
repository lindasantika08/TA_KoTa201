<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Models\TypeCriteria;
use App\Models\Assessment;
use App\Models\Answers;
use App\Models\Project;
use App\Models\User;
use App\Models\Dosen;
use App\Models\Mahasiswa;
use App\Models\Group;
use App\Models\ClassRoom;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\GeminiService;
use Illuminate\Support\Facades\Http;
use App\Jobs\ProcessFlaskAssessment;

class SelfAssessment extends Controller
{
    public function assessment(Request $request)
    {
        Log::info('Request received in assessment:', $request->all());

        $validated = $request->validate([
            'batch_year' => 'required|string',
            'project_name' => 'required|string',
            'assessment_order' => 'required|string',
        ]);

        Log::info('Validated data:', $validated);

        return Inertia::render('Mahasiswa/SelfAssessmentMahasiswa', [
            'batch_year' => $validated['batch_year'],
            'project_name' => $validated['project_name'],
            'assessment_order' => $validated['assessment_order'],
        ]);
    }

    public function getQuestionsByProject(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                throw new \Exception('User not authenticated');
            }

            $batchYear = $request->input('batch_year');
            $projectName = $request->input('project_name');
            $assessmentOrder = $request->input('assessment_order');

            Log::info('Received Parameters', [
                'batch_year' => $batchYear,
                'project_name' => $projectName,
                'assessment_order' => $assessmentOrder
            ]);

            if (empty($batchYear) || empty($projectName) || empty($assessmentOrder)) {
                throw new \Exception('Batch year, project name, and assessment order are required');
            }

            $mahasiswa = Mahasiswa::where('user_id', $user->id)->first();
            if (!$mahasiswa) {
                throw new \Exception('Mahasiswa not found for user ID: ' . $user->id);
            }

            $group = Group::whereHas('project', function ($query) use ($batchYear, $projectName) {
                $query->where('batch_year', $batchYear)
                    ->where('project_name', $projectName);
            })->where('mahasiswa_id', $mahasiswa->id)->first();

            if (!$group) {
                throw new \Exception('Group not found for this project');
            }

            $project = $group->project;
            if (!$project) {
                throw new \Exception('Project not found');
            }

            Log::info('Project Details', [
                'project_id' => $project->id,
                'batch_year' => $project->batch_year,
                'project_name' => $project->project_name
            ]);

            // Filter berdasarkan assessment_order
            $assessments = Assessment::where('project_id', $project->id)
                ->where('type', 'selfAssessment')
                ->where('assessment_order', $assessmentOrder) // Tambah filter berdasarkan assessment_order
                ->where('is_published', 1) // Pastikan hanya mengambil yang sudah dipublish
                ->get();

            if ($assessments->isEmpty()) {
                throw new \Exception('No assessments found for this project and assessment order');
            }

            $formattedAssessments = $assessments->map(function ($assessment) {
                $criteria = TypeCriteria::find($assessment->criteria_id);

                if (!$criteria) {
                    return null;
                }

                return [
                    'id' => $assessment->id,
                    'type' => $assessment->type,
                    'question' => $assessment->question,
                    'skill_type' => $assessment->skill_type,
                    'aspek' => $criteria->aspect,
                    'kriteria' => $criteria->criteria,
                    'bobot_1' => $criteria->bobot_1,
                    'bobot_2' => $criteria->bobot_2,
                    'bobot_3' => $criteria->bobot_3,
                    'bobot_4' => $criteria->bobot_4,
                    'bobot_5' => $criteria->bobot_5,
                ];
            })->filter();

            Log::info('Formatted Assessments', [
                'count' => $formattedAssessments->count(),
                'assessment_order' => $assessmentOrder
            ]);

            return response()->json($formattedAssessments);
        } catch (\Exception $e) {
            Log::error('Error in getQuestionsByProject:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function getUserInfo(Request $request)
    {
        $user = Auth::user();
        $mahasiswa = Mahasiswa::where('user_id', $user->id)->first();

        // Ubah cara pengambilan parameter
        $batch_year = $request->input('batch_year');
        $project_name = $request->input('project_name');

        Log::info('User Info Request', [
            'user_id' => $user->id,
            'batch_year' => $batch_year,
            'project_name' => $project_name
        ]);

        $group = Group::where('mahasiswa_id', $mahasiswa->id)
            ->whereHas('project', function ($query) use ($batch_year, $project_name) {
                $query->where('batch_year', $batch_year)
                    ->where('project_name', $project_name);
            })
            ->with('project')
            ->first();

        if (!$group) {
            // Coba cari group terakhir jika tidak ditemukan
            $group = Group::where('mahasiswa_id', $mahasiswa->id)
                ->with('project')
                ->orderBy('created_at', 'desc')
                ->first();

            if (!$group) {
                return response()->json([
                    'message' => 'No matching group found',
                    'debug' => [
                        'mahasiswa_id' => $mahasiswa->id,
                        'batch_year' => $batch_year,
                        'project_name' => $project_name
                    ]
                ], 404);
            }
        }

        return response()->json([
            'nim' => $mahasiswa->nim,
            'name' => $user->name,
            'class' => $mahasiswa->classRoom->class_name,
            'group' => $group->group,
            'project_name' => $group->project->project_name,
            'batch_year' => $group->project->batch_year,
            'date' => now()->format('d F Y')
        ]);
    }

    // public function saveAnswer(Request $request)
    // {
    //     DB::beginTransaction();
    //     try {
    //         $validated = $request->validate([
    //             'answers' => 'required|array',
    //             'answers.*.question_id' => 'required|uuid',
    //             'answers.*.answer' => 'required|string',
    //             'answers.*.score' => 'required|integer|between:1,5',
    //             'answers.*.status' => 'required|string',
    //         ]);

    //         $user = Auth::user();
    //         $mahasiswa = Mahasiswa::where('user_id', $user->id)->first();

    //         if (!$mahasiswa) {
    //             throw new \Exception('Mahasiswa tidak ditemukan');
    //         }

    //         $savedAnswers = [];
    //         foreach ($validated['answers'] as $answerData) {
    //             $answer = Answers::updateOrCreate(
    //                 [
    //                     'question_id' => $answerData['question_id'],
    //                     'mahasiswa_id' => $mahasiswa->id
    //                 ],
    //                 [
    //                     'question_id' => $answerData['question_id'],
    //                     'mahasiswa_id' => $mahasiswa->id,
    //                     'answer' => $answerData['answer'],
    //                     'score' => $answerData['score'],
    //                     'status' => $answerData['status']
    //                 ]
    //             );
    //             $savedAnswers[] = $answer;
    //         }

    //         DB::commit();

    //         return response()->json([
    //             'message' => 'All answers saved successfully',
    //             'answers' => $savedAnswers
    //         ]);
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         Log::error('Error in saveAnswer:', [
    //             'error' => $e->getMessage(),
    //             'trace' => $e->getTraceAsString()
    //         ]);

    //         return response()->json([
    //             'error' => 'Failed to save answers: ' . $e->getMessage()
    //         ], 500);
    //     }
    // }

    public function saveAnswer(Request $request)
    {
        DB::beginTransaction();
        try {
            $validated = $request->validate([
                'answers' => 'required|array',
                'answers.*.question_id' => 'required|uuid',
                'answers.*.answer' => 'required|string',
                'answers.*.score' => 'required|integer|between:1,5',
                'answers.*.status' => 'required|string',
            ]);

            $user = Auth::user();
            $mahasiswa = Mahasiswa::where('user_id', $user->id)->first();

            if (!$mahasiswa) {
                throw new \Exception('Mahasiswa tidak ditemukan');
            }

            $savedAnswers = [];
            $flaskResults = [];

            foreach ($validated['answers'] as $answerData) {
                // Get the question to access its criteria
                $question = Assessment::with('typeCriteria')->where('id', $answerData['question_id'])->first();

                if (!$question) {
                    throw new \Exception('Question not found');
                }

                // Get the criteria information based on the score
                $typeCriteria = $question->typeCriteria;

                // Create a criteria dictionary for the Flask service
                $criteriaDict = $this->prepareCriteriaDictionary($typeCriteria);

                // Store the initial answer
                $answer = Answers::updateOrCreate(
                    [
                        'question_id' => $answerData['question_id'],
                        'mahasiswa_id' => $mahasiswa->id
                    ],
                    [
                        'question_id' => $answerData['question_id'],
                        'mahasiswa_id' => $mahasiswa->id,
                        'answer' => $answerData['answer'],
                        'score' => $answerData['score'],
                        'status' => $answerData['status']
                    ]
                );

                // Prepare data for Flask service
                $flaskData = [
                    'answer' => $answerData['answer'], // Send original answer
                    'score_given' => $answerData['score'],
                    'criteria' => $criteriaDict
                ];

                // Log the complete Flask data for debugging
                Log::info('Sending data to Flask service:', [
                    'answer_id' => $answer->id,
                    'flask_data' => $flaskData
                ]);

                // Send to Flask service for assessment
                try {
                    $flaskResult = $this->sendToFlaskService($flaskData);

                    if ($flaskResult) {
                        // Update the answer with SLA score and similarity
                        $answer->update([
                            'score_SLA' => $flaskResult['best_score'] ?? null,
                            'similarity' => $flaskResult['best_similarity'] ?? null
                        ]);

                        $flaskResults[] = [
                            'answer_id' => $answer->id,
                            'score_SLA' => $flaskResult['best_score'] ?? null,
                            'similarity' => $flaskResult['best_similarity'] ?? null,
                            'sentiment' => $flaskResult['sentiment'] ?? null,
                            'similarity_scores' => $flaskResult['similarity_scores'] ?? null
                        ];

                        Log::info('Successfully processed answer with Flask', [
                            'answer_id' => $answer->id,
                            'flask_result' => $flaskResult
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('Error connecting to Flask service', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                        'answer_id' => $answer->id
                    ]);
                }

                $savedAnswers[] = $answer->fresh();
            }

            DB::commit();

            return response()->json([
                'message' => 'All answers saved successfully',
                'answers' => $savedAnswers,
                'flask_results' => $flaskResults
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in saveAnswer:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to save answers: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Prepare criteria dictionary without translations
     * 
     * @param mixed $typeCriteria
     * @return array
     */
    private function prepareCriteriaDictionary($typeCriteria)
    {
        $criteriaDict = [];

        if ($typeCriteria) {
            Log::info('TypeCriteria data:', [
                'type_criteria_id' => $typeCriteria->id,
                'bobot_1' => $typeCriteria->bobot_1,
                'bobot_2' => $typeCriteria->bobot_2,
                'bobot_3' => $typeCriteria->bobot_3,
                'bobot_4' => $typeCriteria->bobot_4,
                'bobot_5' => $typeCriteria->bobot_5,
            ]);

            // Process each criteria individually to ensure better tracking
            for ($i = 1; $i <= 5; $i++) {
                $bobotField = "bobot_" . $i;

                if (isset($typeCriteria->$bobotField) && !empty($typeCriteria->$bobotField)) {
                    $criteriaDict[$i] = $typeCriteria->$bobotField;
                } else {
                    $criteriaDict[$i] = "No criteria defined for score $i";
                    Log::warning("Missing criteria for bobot_{$i}");
                }
            }
        } else {
            // Use default criteria if typeCriteria is not found
            $criteriaDict = [
                1 => "Did not meet any requirements",
                2 => "Met few requirements",
                3 => "Met some requirements",
                4 => "Met most requirements",
                5 => "Met all requirements"
            ];
            Log::warning('TypeCriteria not found, using default criteria');
        }

        // Final verification - ensure all criteria are present
        foreach ($criteriaDict as $score => $criteria) {
            Log::info("Final criteria for score {$score}: {$criteria}");
        }

        return $criteriaDict;
    }

    /**
     * Send data to Flask service
     * 
     * @param array $flaskData
     * @return array|null
     */
    private function sendToFlaskService($flaskData)
    {
        // Replace with your actual Flask endpoint
        $flaskUrl = env('FLASK_SERVICE_URL', 'http://localhost:5000/assess');

        try {
            $flaskResponse = Http::timeout(30)->withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ])->post($flaskUrl, $flaskData);

            if ($flaskResponse->successful()) {
                return $flaskResponse->json();
            } else {
                Log::error('Flask service returned an error', [
                    'status' => $flaskResponse->status(),
                    'response' => $flaskResponse->body()
                ]);
                return null;
            }
        } catch (\Exception $e) {
            Log::error('Error connecting to Flask service', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }


    // public function saveAllAnswers(Request $request)
    // {
    //     DB::beginTransaction();
    //     try {
    //         $validated = $request->validate([
    //             'answers' => 'required|array',
    //             'answers.*.question_id' => 'required|uuid',
    //             'answers.*.answer' => 'required|string',
    //             'answers.*.score' => 'required|integer|between:1,5',
    //             'answers.*.status' => 'required|string'
    //         ]);

    //         $user = Auth::user();
    //         $mahasiswa = Mahasiswa::where('user_id', $user->id)->first();

    //         if (!$mahasiswa) {
    //             throw new \Exception('Mahasiswa tidak ditemukan untuk user_id: ' . $user->id);
    //         }

    //         foreach ($validated['answers'] as $answerData) {
    //             $dataToSave = [
    //                 'question_id' => $answerData['question_id'],
    //                 'answer' => $answerData['answer'],
    //                 'score' => $answerData['score'],
    //                 'status' => $answerData['status'],
    //                 'mahasiswa_id' => $mahasiswa->id
    //             ];

    //             Answers::updateOrCreate(
    //                 [
    //                     'question_id' => $dataToSave['question_id'],
    //                     'mahasiswa_id' => $mahasiswa->id
    //                 ],
    //                 $dataToSave
    //             );
    //         }

    //         DB::commit();
    //         return response()->json([
    //             'success' => true,
    //             'message' => 'All answers saved successfully.'
    //         ]);
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         Log::error('Error in saveAllAnswers:', [
    //             'error' => $e->getMessage(),
    //             'trace' => $e->getTraceAsString()
    //         ]);

    //         return response()->json([
    //             'success' => false,
    //             'error' => 'Failed to save answers: ' . $e->getMessage()
    //         ], 500);
    //     }
    // }



    public function saveAllAnswers(Request $request)
    {
        DB::beginTransaction();
        try {
            $validated = $request->validate([
                'answers' => 'required|array',
                'answers.*.question_id' => 'required|uuid',
                'answers.*.answer' => 'required|string',
                'answers.*.score' => 'required|integer|between:1,5',
                'answers.*.status' => 'required|string'
            ]);

            $user = Auth::user();
            $mahasiswa = Mahasiswa::where('user_id', $user->id)->first();

            if (!$mahasiswa) {
                throw new \Exception('Mahasiswa tidak ditemukan untuk user_id: ' . $user->id);
            }

            $savedAnswers = [];

            // Simpan semua jawaban terlebih dahulu tanpa menunggu hasil Flask
            foreach ($validated['answers'] as $answerData) {
                $answer = Answers::updateOrCreate(
                    [
                        'question_id' => $answerData['question_id'],
                        'mahasiswa_id' => $mahasiswa->id
                    ],
                    [
                        'question_id' => $answerData['question_id'],
                        'mahasiswa_id' => $mahasiswa->id,
                        'answer' => $answerData['answer'],
                        'score' => $answerData['score'],
                        'status' => $answerData['status']
                    ]
                );

                $savedAnswers[] = $answer;

                // Kirim job ke queue untuk diproses di background
                // Hanya kirim data minimun yang diperlukan
                $simpleAnswerData = [
                    'question_id' => $answerData['question_id'],
                    'answer' => $answerData['answer'],
                    'score' => $answerData['score']
                ];

                ProcessFlaskAssessment::dispatch($simpleAnswerData, $answer->id)
                    ->onQueue('flask-processing'); // Gunakan queue spesifik (opsional)
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'All answers saved successfully. Assessment will be processed in background.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error in saveAllAnswers:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to save answers: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getAnswer($questionId)
    {
        try {
            $user = Auth::user();
            $mahasiswa = Mahasiswa::where('user_id', $user->id)->first();

            if (!$mahasiswa) {
                throw new \Exception('Mahasiswa tidak ditemukan');
            }

            $answer = Answers::where([
                'question_id' => $questionId,
                'mahasiswa_id' => $mahasiswa->id
            ])->first();

            if (!$answer) {
                return response()->json(null);
            }

            return response()->json([
                'answer' => $answer->answer,
                'score' => $answer->score
            ]);
        } catch (\Exception $e) {
            Log::error('Error in getAnswer:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to get answer: ' . $e->getMessage()
            ], 500);
        }
    }
}
