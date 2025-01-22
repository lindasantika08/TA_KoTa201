<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Models\type_criteria;
use App\Models\Assessment;
use App\Models\Answers;
use App\Models\project;
use App\Models\User;
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
            'tahunAjaran' => 'required|string',
            'namaProyek' => 'required|string',
        ]);

        Log::info('Validated data:', $validated);

        return Inertia::render('Mahasiswa/SelfAssessmentMahasiswa', [
            'tahunAjaran' => $validated['tahunAjaran'],
            'namaProyek' => $validated['namaProyek'],
        ]);
    }

    public function getQuestionsByProject(Request $request)
    {
        $tahunAjaran = $request->query('tahun_ajaran');
        $namaProyek = $request->query('nama_proyek');

        $assessments = Assessment::join('type_criteria', function ($join) {
            $join->on('assessment.aspek', '=', 'type_criteria.aspek')
                ->on('assessment.kriteria', '=', 'type_criteria.kriteria');
        })
            ->select(
                'assessment.id',
                'assessment.type',
                'assessment.pertanyaan',
                'assessment.aspek',
                'assessment.kriteria',
                'type_criteria.bobot_1',
                'type_criteria.bobot_2',
                'type_criteria.bobot_3',
                'type_criteria.bobot_4',
                'type_criteria.bobot_5'
            )
            ->when($tahunAjaran, function ($query, $tahunAjaran) {
                $query->where('assessment.tahun_ajaran', $tahunAjaran);
            })
            ->when($namaProyek, function ($query, $namaProyek) {
                $query->where('assessment.nama_proyek', $namaProyek);
            })
            ->where('assessment.type', 'selfAssessment')
            ->get();

        return response()->json($assessments);
    }


    public function getFilteredBobot(Request $request)
    {
        try {
            $bobot = type_criteria::where('aspek', $request->aspek)
                ->where('kriteria', $request->kriteria)
                ->get(['bobot_1', 'bobot_2', 'bobot_3', 'bobot_4', 'bobot_5'])
                ->first();
            return response()->json($bobot ?? []);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function saveAnswer(Request $request)
    {
        DB::beginTransaction();
        try {
            $validated = $request->validate([
                'question_id' => 'required|uuid',
                'answer' => 'required|string',
                'score' => 'required|integer|between:1,5',
                'status' => 'required|string'
            ]);

            $answer = Answers::updateOrCreate(
                [
                    'question_id' => $validated['question_id'],
                    'user_id' => auth()->id()
                ],
                [
                    'answer' => $validated['answer'],
                    'score' => $validated['score'],
                    'status' => $validated['status']
                ]
            );

            DB::commit();

            return response()->json([
                'message' => $answer->wasRecentlyCreated ?
                    'Answer saved successfully' :
                    'Answer updated successfully',
                'answer' => $answer
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Failed to save answer: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getUserInfo(Request $request)
    {
        $user = Auth::user();

        $userInfo = [
            'nim' => $user->nim,
            'name' => $user->name,
            'class' => '1B',
            'group' => '1 (Satu)',
            'project' => 'Aplikasi Perkantoran',
            'date' => now()->format('d F Y')
        ];

        return response()->json($userInfo);
    }

    public function getAnswer($questionId)
    {
        try {
            $answer = Answers::where('question_id', $questionId)
                ->where('user_id', auth()->id())
                ->first();

            return response()->json($answer);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
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

            foreach ($validated['answers'] as $answerData) {
                Answers::updateOrCreate(
                    [
                        'question_id' => $answerData['question_id'],
                        'user_id' => auth()->id()
                    ],
                    [
                        'answer' => $answerData['answer'],
                        'score' => $answerData['score'],
                        'status' => $answerData['status']
                    ]
                );
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Semua jawaban berhasil disimpan'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
