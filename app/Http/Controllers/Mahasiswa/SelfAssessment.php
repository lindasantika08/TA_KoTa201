<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Models\TypeCriteria;
use App\Models\Assessment;
use App\Models\Answers;
use App\Models\project;
use App\Models\User;
use App\Models\Dosen;
use App\Models\Mahasiswa;
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
        $batchYear = $request->query('batch_year');
        $projectName = $request->query('project_name');

        // First find the project
        $project = Project::where('batch_year', $batchYear)
            ->where('project_name', $projectName)
            ->first();

        if (!$project) {
            return response()->json(['error' => 'Project not found'], 404);
        }

        $assessments = Assessment::with('typeCriteria')
            ->where('project_id', $project->id)
            ->where('type', 'selfAssessment')
            ->get()
            ->map(function ($assessment) {
                return [
                    'id' => $assessment->id,
                    'type' => $assessment->type,
                    'pertanyaan' => $assessment->question,
                    'aspek' => $assessment->typeCriteria->aspect,
                    'kriteria' => $assessment->typeCriteria->criteria,
                    'bobot_1' => $assessment->typeCriteria->bobot_1,
                    'bobot_2' => $assessment->typeCriteria->bobot_2,
                    'bobot_3' => $assessment->typeCriteria->bobot_3,
                    'bobot_4' => $assessment->typeCriteria->bobot_4,
                    'bobot_5' => $assessment->typeCriteria->bobot_5,
                ];
            });

        return response()->json($assessments);
    }


    public function getFilteredBobot(Request $request)
    {
        try {
            $bobot = TypeCriteria::where('aspek', $request->aspek)
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
            // Validasi input
            $validated = $request->validate([
                'question_id' => 'required|uuid',
                'answer' => 'required|string',
                'score' => 'required|integer|between:1,5',
                'status' => 'required|string',
                'role' => 'required|string|in:dosen,mahasiswa' // Validasi role
            ]);

            // Ambil informasi pengguna yang sedang login
            $user = Auth::user();
            $userId = $user->id;

            // Ambil data dosen berdasarkan user_id
            $dosen = Dosen::where('user_id', $userId)->first();

            // Log informasi tentang dosen
            Log::info('Dosen retrieved:', [
                'dosen_id' => $dosen ? $dosen->id : null,
                'user_id' => $userId
            ]);

            // Siapkan data untuk disimpan
            $answerData = [
                'question_id' => $validated['question_id'],
                'answer' => $validated['answer'],
                'score' => $validated['score'],
                'status' => $validated['status'],
            ];

            // Tentukan ID berdasarkan role yang diterima dari frontend
            if ($validated['role'] === 'dosen') {
                if ($dosen) {
                    $answerData['dosen_id'] = $dosen->id; // Simpan ID dosen
                } else {
                    throw new \Exception('Dosen tidak ditemukan untuk user_id: ' . $userId);
                }
                $answerData['mahasiswa_id'] = null; // Pastikan mahasiswa_id null
            } elseif ($validated['role'] === 'mahasiswa') {
                $answerData['mahasiswa_id'] = $userId; // Simpan ID mahasiswa
                $answerData['dosen_id'] = null; // Pastikan dosen_id null
            }

            // Simpan atau perbarui jawaban
            $answer = Answers::updateOrCreate(
                [
                    'question_id' => $validated['question_id'],
                    'dosen_id' => $answerData['dosen_id'],
                    'mahasiswa_id' => $answerData['mahasiswa_id'],
                ],
                $answerData
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
            Log::error('Error in saveAnswer:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

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
            // Validasi input
            $validated = $request->validate([
                'answers' => 'required|array',
                'answers.*.question_id' => 'required|uuid',
                'answers.*.answer' => 'required|string',
                'answers.*.score' => 'required|integer|between:1,5',
                'answers.*.status' => 'required|string'
            ]);

            // Ambil informasi pengguna yang sedang login
            $user = Auth::user();
            $userId = $user->id;

            // Ambil dosen berdasarkan user_id
            $dosen = Dosen::where('user_id', $userId)->first();
            \Log::info('Dosen retrieved:', ['dosen' => $dosen]);

            // Loop melalui setiap jawaban dan simpan
            foreach ($validated['answers'] as $answerData) {
                // Siapkan data untuk disimpan
                $dataToSave = [
                    'question_id' => $answerData['question_id'],
                    'answer' => $answerData['answer'],
                    'score' => $answerData['score'],
                    'status' => $answerData['status'],
                    'dosen_id' => $dosen ? $dosen->id : null, // Simpan ID dosen jika ada
                    'mahasiswa_id' => null // Atur mahasiswa_id jika diperlukan
                ];

                // Jika role adalah mahasiswa, ambil mahasiswa_id
                if ($request->input('role') === 'mahasiswa') {
                    $dataToSave['mahasiswa_id'] = $userId; // Simpan ID mahasiswa
                    $dataToSave['dosen_id'] = null; // Pastikan dosen_id null
                }

                // Simpan atau perbarui jawaban
                Answers::updateOrCreate(
                    [
                        'question_id' => $dataToSave['question_id'],
                        'dosen_id' => $dataToSave['dosen_id'],
                        'mahasiswa_id' => $dataToSave['mahasiswa_id'],
                    ],
                    $dataToSave
                );
            }

            DB::commit();

            return response()->json(['message' => 'All answers saved successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error in saveAllAnswers:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json(['error' => 'Failed to save answers: ' . $e->getMessage()], 500);
        }
    }
}
