<?php

namespace App\Http\Controllers\Dosen;

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

class SelfAssessmentDosen extends Controller
{

    public function getUserInfoDosen(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json(['error' => 'User not authenticated'], 401);
            }

            $dosen = Dosen::with('user')
                ->where('user_id', $user->id)
                ->first();

            if (!$dosen) {
                return response()->json(['error' => 'Dosen record not found'], 404);
            }

            $kelompok = Group::where('dosen_id', $dosen->id)
                ->with('project') 
                ->first();

            $userInfo = [
                'id' => $dosen->id,
                'nip' => $dosen->nip,
                'name' => $user->name,
                'group' => $kelompok ? $kelompok->name : 'Tidak Ditemukan',
                'project' => $kelompok && $kelompok->project ? $kelompok->project->project_name : 'Tidak Ditemukan',
                'date' => now()->format('d F Y')
            ];

            return response()->json($userInfo);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch user info',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getQuestionsByProject(Request $request)
    {
        $tahunAjaran = $request->query('batch_year');
        $namaProyek = $request->query('project_name');

        $project = Project::where('batch_year', $tahunAjaran)
            ->where('project_name', $namaProyek)
            ->first();

        if (!$project) {
            return response()->json(['error' => 'Project not found'], 404);
        }

        $assessments = Assessment::with('typeCriteria')
            ->where('project_id', $project->id)
            ->where('type', 'selfAssessment')
            ->get()
            ->map(function ($assessment) {
                $criteria = TypeCriteria::find($assessment->criteria_id);
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
            });

        return response()->json($assessments);
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
            $dosen = Dosen::where('user_id', $user->id)->first();
            
            if (!$dosen) {
                throw new \Exception('Dosen tidak ditemukan');
            }

            $savedAnswers = [];
            foreach ($validated['answers'] as $answerData) {
                $answer = Answers::updateOrCreate(
                    [
                        'question_id' => $answerData['question_id'],
                        'dosen_id' => $dosen->id
                    ],
                    [
                        'question_id' => $answerData['question_id'],
                        'dosen_id' => $dosen->id,
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

    public function getAnswer($questionId)
    {
        try {
            $user = Auth::user();
            $dosen = Dosen::where('user_id', $user->id)->first();
            
            if (!$dosen) {
                throw new \Exception('Dosen tidak ditemukan');
            }

            $answer = Answers::where([
                'question_id' => $questionId,
                'dosen_id' => $dosen->id
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
            $dosen = Dosen::where('user_id', $user->id)->first();
            
            if (!$dosen) {
                throw new \Exception('Dosen tidak ditemukan untuk user_id: ' . $user->id);
            }
    
            foreach ($validated['answers'] as $answerData) {
                $dataToSave = [
                    'question_id' => $answerData['question_id'],
                    'answer' => $answerData['answer'],
                    'score' => $answerData['score'],
                    'status' => $answerData['status'],
                    'dosen_id' => $dosen->id
                ];
    
                Answers::updateOrCreate(
                    [
                        'question_id' => $dataToSave['question_id'],
                        'dosen_id' => $dosen->id
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
}
