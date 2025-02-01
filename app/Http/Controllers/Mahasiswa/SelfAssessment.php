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

class SelfAssessment extends Controller
{
    public function assessment(Request $request)
    {
        Log::info('Request received in assessment:', $request->all());

        $validated = $request->validate([
            'batch_year' => 'required|string',
            'project_name' => 'required|string',
        ]);

        Log::info('Validated data:', $validated);

        return Inertia::render('Mahasiswa/SelfAssessmentMahasiswa', [
            'batch_year' => $validated['batch_year'],
            'project_name' => $validated['project_name'],
        ]);
    }

    public function getQuestionsByProject(Request $request)
    {
        try {
            $user = Auth::user();
            // dd($user);
            if (!$user) {
                throw new \Exception('User not authenticated');
            }
            Log::info('User found:', ['id' => $user->id, 'name' => $user->name]);

            $mahasiswa = Mahasiswa::where('user_id', $user->id)->first();
            if (!$mahasiswa) {
                throw new \Exception('Mahasiswa not found for user ID: ' . $user->id);
            }
            Log::info('Mahasiswa found:', ['id' => $mahasiswa->id, 'nim' => $mahasiswa->nim]);

            $group = Group::where('mahasiswa_id', $mahasiswa->id)->first();
            if (!$group) {
                throw new \Exception('Group not found for mahasiswa ID: ' . $mahasiswa->id);
            }
            Log::info('Group found:', [
                'id' => $group->id, 
                'group' => $group->group,
                'project_id' => $group->project_id
            ]);

            $project = Project::find($group->project_id);
            if (!$project) {
                throw new \Exception('Project not found for ID: ' . $group->project_id);
            }
            Log::info('Project found:', [
                'id' => $project->id,
                'name' => $project->project_name,
                'batch_year' => $project->batch_year
            ]);

            $assessments = Assessment::with('typeCriteria')
                ->where('project_id', $project->id)
                ->where('type', 'selfAssessment')
                ->get();

            if ($assessments->isEmpty()) {
                throw new \Exception('No assessments found for project ID: ' . $project->id);
            }

            $formattedAssessments = $assessments->map(function ($assessment) {
                $criteria = TypeCriteria::find($assessment->criteria_id);
                return [
                    'id' => $assessment->id,
                    'type' => $assessment->type,
                    'question' => $assessment->question,
                    'aspek' => $criteria->aspect,
                    'kriteria' => $criteria->criteria,
                    'bobot_1' => $criteria->bobot_1,
                    'bobot_2' => $criteria->bobot_2,
                    'bobot_3' => $criteria->bobot_3,
                    'bobot_4' => $criteria->bobot_4,
                    'bobot_5' => $criteria->bobot_5,
                ];
            });

            Log::info('Assessments found:', ['count' => $formattedAssessments->count()]);

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
        $mahasiswa = Mahasiswa::with(['classRoom', 'classRoom.prodi', 'classRoom.prodi.major'])
            ->where('user_id', $user->id)
            ->first();
        
        $group = Group::where('mahasiswa_id', $mahasiswa->id)
            ->with(['project', 'mahasiswa'])
            ->first();

        $userInfo = [
            'nim' => $mahasiswa->nim,
            'name' => $user->name,
            'class' => $mahasiswa->classRoom->class_name,
            'group' => $group ? $group->group : 'Not Assigned',
            'project' => $group ? $group->project->project_name : 'Not Assigned',
            'date' => now()->format('d F Y')
        ];

        return response()->json($userInfo);
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
            }

            DB::commit();

            return response()->json([
                'message' => 'All answers saved successfully',
                'answers' => $savedAnswers
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
    
            foreach ($validated['answers'] as $answerData) {
                $dataToSave = [
                    'question_id' => $answerData['question_id'],
                    'answer' => $answerData['answer'],
                    'score' => $answerData['score'],
                    'status' => $answerData['status'],
                    'mahasiswa_id' => $mahasiswa->id
                ];
    
                Answers::updateOrCreate(
                    [
                        'question_id' => $dataToSave['question_id'],
                        'mahasiswa_id' => $mahasiswa->id
                    ],
                    $dataToSave
                );
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