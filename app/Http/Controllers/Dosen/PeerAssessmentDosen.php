<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use App\Models\TypeCriteria;
use App\Models\Assessment;
use App\Models\Answers;
use App\Models\AnswersPeer;
use App\Models\project;
use App\Models\User;
use App\Models\Dosen;
use App\Models\Mahasiswa;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PeerAssessmentDosen extends Controller
{
    public function index() {

    }

    public function getAssessmentsWithBobotPeer(Request $request)
    {
        $tahunAjaran = $request->query('batch_year');
        $namaProyek = $request->query('project_name');

        $assessments = Assessment::with(['typeCriteria', 'project'])
            ->select(
                'assessment.id',
                'assessment.type',
                'assessment.question',
                'assessment.criteria_id',
                'assessment.project_id'
            )
            ->whereHas('project', function($query) use ($namaProyek) {
                $query->where('project_name', $namaProyek);
            })
            ->when($tahunAjaran, function ($query, $tahunAjaran) {
                $query->where('batch_year', $tahunAjaran);
            })
            ->where('type', 'peerAssessment')
            ->get()
            ->map(function($assessment) {
                return [
                    'id' => $assessment->id,
                    'type' => $assessment->type,
                    'question' => $assessment->question,
                    'aspect' => $assessment->typeCriteria->aspect,
                    'criteria' => $assessment->typeCriteria->criteria,
                    'bobot_1' => $assessment->typeCriteria->bobot_1,
                    'bobot_2' => $assessment->typeCriteria->bobot_2,
                    'bobot_3' => $assessment->typeCriteria->bobot_3,
                    'bobot_4' => $assessment->typeCriteria->bobot_4,
                    'bobot_5' => $assessment->typeCriteria->bobot_5,
                ];
            });

        return Inertia::render('Dosen/PeerAssessment', [
            'assessments' => $assessments,
            'tahunAjaran' => $tahunAjaran,
            'namaProyek' => $namaProyek,
        ]);
    }

    public function getQuestionsByProjectPeer(Request $request)
    {
        $tahunAjaran = $request->query('batch_year');
        $namaProyek = $request->query('project_name');

        $assessments = Assessment::join('type_criteria', 'assessment.criteria_id', '=', 'type_criteria.id')
            ->join('project', 'assessment.project_id', '=', 'project.id')
            ->select(
                'assessment.id',
                'assessment.type',
                'assessment.question',
                'type_criteria.aspect',
                'type_criteria.criteria',
                'type_criteria.bobot_1',
                'type_criteria.bobot_2',
                'type_criteria.bobot_3',
                'type_criteria.bobot_4',
                'type_criteria.bobot_5'
            )
            ->when($tahunAjaran, function ($query, $tahunAjaran) {
                $query->where('assessment.batch_year', $tahunAjaran);
            })
            ->when($namaProyek, function ($query, $namaProyek) {
                $query->where('project.project_name', $namaProyek);
            })
            ->where('assessment.type', 'peerAssessment')
            ->get();

        return response()->json($assessments);
    }

    public function saveAnswerPeer(Request $request)
    {
        DB::beginTransaction();
        try {
            $user = Auth::user();
            $dosen = Dosen::where('user_id', $user->id)->first();
            
            $savedAnswers = [];
            foreach ($request->input('answers') as $answerData) {
                $answer = AnswersPeer::updateOrCreate(
                    [
                        'question_id' => $answerData['question_id'],
                        'dosen_id' => $dosen->id
                    ],
                    [
                        'answer' => $answerData['answer'],
                        'score' => $answerData['score'],
                        'status' => 'submitted'
                    ]
                );
                $savedAnswers[] = $answer;
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'answers' => $savedAnswers
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function saveAllAnswersPeerDosen(Request $request)
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
                throw new \Exception('dosen tidak ditemukan');
            }

            $savedAnswers = [];
            foreach ($validated['answers'] as $answerData) {
                $answer = AnswersPeer::updateOrCreate(
                    [
                        'question_id' => $answerData['question_id'],
                        'dosen_id' => $dosen->id
                    ],
                    [
                        'answer' => $answerData['answer'],
                        'score' => $answerData['score'],
                        'status' => $answerData['status']
                    ]
                );
                $savedAnswers[] = $answer;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'All answers saved successfully',
                'answers' => $savedAnswers
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in saveAllAnswersPeerDosen:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to save answers: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getAnswerPeer($questionId, Request $request)
    {
        try {
            $validated = $request->validate([
                'dosen_id' => 'required|string|exists:dosen,id',
            ]);

            $answer = AnswersPeer::where([
                'dosen_id' => $validated['dosen_id'],
                'question_id' => $questionId
            ])->first();

            return response()->json($answer);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error in getAnswerPeer:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil jawaban.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
