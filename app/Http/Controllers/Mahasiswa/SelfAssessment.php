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

            $assessments = Assessment::where('project_id', $project->id)
                ->where('type', 'selfAssessment')
                ->where('assessment_order', $assessmentOrder)
                ->where('is_published', 1) 
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
        
        $batch_year = $request->input('batch_year');
        $project_name = $request->input('project_name');

        Log::info('User Info Request', [
            'user_id' => $user->id,
            'batch_year' => $batch_year,
            'project_name' => $project_name
        ]);
        Log::info('User Info Request', [
            'user_id' => $user->id,
            'batch_year' => $batch_year,
            'project_name' => $project_name
        ]);

        $group = Group::where('mahasiswa_id', $mahasiswa->id)
            ->whereHas('project', function($query) use ($batch_year, $project_name) {
                $query->where('batch_year', $batch_year)
                    ->where('project_name', $project_name);
            })
            ->with('project')
            ->first();

        if (!$group) {
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
                'temporaryAnswers' => 'sometimes|array'
            ]);
            
            $user = Auth::user();
            $mahasiswa = Mahasiswa::where('user_id', $user->id)->first();
            
            if (!$mahasiswa) {
                throw new \Exception('Mahasiswa tidak ditemukan');
            }
            
            $savedAnswers = [];
            
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
                
                $simpleAnswerData = [
                    'question_id' => $answerData['question_id'],
                    'answer' => $answerData['answer'],
                    'score' => $answerData['score']
                ];
                
                ProcessFlaskAssessment::dispatch($simpleAnswerData, $answer->id)
                    ->onQueue('flask-processing');
            }
            
            // Next, save all temporary answers if provided
            if (isset($validated['temporaryAnswers']) && !empty($validated['temporaryAnswers'])) {
                foreach ($validated['temporaryAnswers'] as $questionId => $tempAnswer) {
                    // Skip questions that were just saved in the first loop
                    if (in_array($questionId, array_column($validated['answers'], 'question_id'))) {
                        continue;
                    }
                    
                    // Ensure the temporary answer has the required fields
                    if (isset($tempAnswer['answer']) && isset($tempAnswer['score'])) {
                        $answer = Answers::updateOrCreate(
                            [
                                'question_id' => $questionId,
                                'mahasiswa_id' => $mahasiswa->id
                            ],
                            [
                                'question_id' => $questionId,
                                'mahasiswa_id' => $mahasiswa->id,
                                'answer' => $tempAnswer['answer'],
                                'score' => $tempAnswer['score'],
                                'status' => $request->input('answers.0.status', 'submitted') // Use the same status as main answers
                            ]
                        );
                        
                        $savedAnswers[] = $answer;
                        
                        $simpleAnswerData = [
                            'question_id' => $questionId,
                            'answer' => $tempAnswer['answer'],
                            'score' => $tempAnswer['score']
                        ];
                        
                        ProcessFlaskAssessment::dispatch($simpleAnswerData, $answer->id)
                            ->onQueue('flask-processing');
                    }
                }
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'All answers saved successfully.',
                'answers' => $savedAnswers
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error in saveAnswer:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to save answers: ' . $e->getMessage()
            ], 500);
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
                'message' => 'All answers saved successfully.'
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
