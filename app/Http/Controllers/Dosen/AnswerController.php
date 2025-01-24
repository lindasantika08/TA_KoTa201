<?php

namespace App\Http\Controllers\Dosen;

use App\Models\Kelompok;
use App\Models\Answers;
use App\Models\User;
use App\Models\AnswersPeer;
use App\Models\Assessment;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AnswerController extends Controller
{
    public function answerSelf(Request $request)
    {
        $validated = $request->validate([
            'tahunAjaran' => 'required|string',
            'namaProyek' => 'required|string',
        ]);

        Log::info('AnswerSelf method called', [
            'tahunAjaran' => $validated['tahunAjaran'],
            'namaProyek' => $validated['namaProyek'],
        ]);

        return Inertia::render('Dosen/AnswerSelf', [
            'tahunAjaran' => $validated['tahunAjaran'],
            'namaProyek' => $validated['namaProyek'],
        ]);
    }

    public function answerPeer(Request $request)
    {
        $validated = $request->validate([
            'tahunAjaran' => 'required|string',
            'namaProyek' => 'required|string',
        ]);

        Log::info('AnswerPeer method called', [
            'tahunAjaran' => $validated['tahunAjaran'],
            'namaProyek' => $validated['namaProyek'],
        ]);

        return Inertia::render('Dosen/AnswerPeer', [
            'tahunAjaran' => $validated['tahunAjaran'],
            'namaProyek' => $validated['namaProyek'],
        ]);
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
                'answers.*.status' => 'required|string'
            ]);

            foreach ($validated['answers'] as $answerData) {
                AnswersPeer::updateOrCreate(
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
            Log::info('Transaction committed');
            return response()->json([
                'success' => true,
                'message' => 'Semua jawaban berhasil disimpan'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Transaction rolled back', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function getQuestionId(Request $request)
    {
        $validated = $request->validate([
            'tahun_ajaran' => 'required|string',
            'nama_proyek' => 'required|string',
        ]);

        $assessment = Assessment::where('tahun_ajaran', $validated['tahun_ajaran'])
            ->where('nama_proyek', $validated['nama_proyek'])
            ->first();

        if (!$assessment) {
            return response()->json(['message' => 'Assessment not found'], 404);
        }

        return response()->json([
            'questionId' => $assessment->id,
        ]);
    }

    public function getUserInfoDosen(Request $request)
    {
        $user = Auth::user();

        $kelompok = Kelompok::where('dosen_id', $user->id)
            ->first();

        $userInfo = [
            'id' => $user->id,
            'nip' => $user->nip,
            'name' => $user->name,
            'class' => '1B',
            'group' => $kelompok ? $kelompok->kelompok : 'Tidak Ditemukan',
            'project' => $kelompok ? $kelompok->nama_proyek : 'Tidak Ditemukan',
            'date' => now()->format('d F Y')
        ];

        return response()->json($userInfo);
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
            ->where('assessment.tahun_ajaran', $tahunAjaran)
            ->where('assessment.nama_proyek', $namaProyek)
            ->where('assessment.type', 'selfAssessment')
            ->get();

        return response()->json($assessments);
    }

    public function getListAnswers(Request $request)
    {
        $tahunAjaran = $request->query('tahun_ajaran');
        $namaProyek = $request->query('nama_proyek');

        $totalQuestions = Assessment::where('tahun_ajaran', $tahunAjaran)
            ->where('nama_proyek', $namaProyek)
            ->where('type', 'selfAssessment')
            ->count();

        if ($totalQuestions == 0) {
            return response()->json(['message' => 'No self-assessment questions found for the specified year and project.'], 404);
        }

        $usersInKelompok = Kelompok::where('tahun_ajaran', $tahunAjaran)
            ->where('nama_proyek', $namaProyek)
            ->pluck('user_id');

        $answers = Answers::whereIn('user_id', $usersInKelompok)
            ->join('assessment', 'answers.question_id', '=', 'assessment.id')
            ->where('assessment.tahun_ajaran', $tahunAjaran)
            ->where('assessment.nama_proyek', $namaProyek)
            ->where('assessment.type', 'selfAssessment')
            ->get();

        $userAnswers = $answers->groupBy('user_id');

        $result = [];

        foreach ($usersInKelompok as $userId) {
            $user = User::find($userId);

            $userAnswered = isset($userAnswers[$userId]) ? $userAnswers[$userId] : collect();

            $answeredQuestionIds = $userAnswered->pluck('question_id');

            if ($answeredQuestionIds->count() === 0) {
                $status = 'unsubmitted';
            } elseif ($answeredQuestionIds->count() < $totalQuestions) {
                $status = 'on progress';
            } elseif ($answeredQuestionIds->count() === $totalQuestions) {
                $status = 'submitted';
            } else {
                $status = 'unsubmitted';
            }

            $result[] = [
                'user' => $user,
                'status' => $status,
                'answers' => $userAnswered,
            ];
        }

        return response()->json($result);
    }
    public function getStatisticsPeer(Request $request)
    {
        $tahunAjaran = $request->query('tahun_ajaran');
        $namaProyek = $request->query('nama_proyek');

        Log::info('Mendapatkan Statistik:', ['tahun_ajaran' => $tahunAjaran, 'nama_proyek' => $namaProyek]);

        // Ambil semua data kelompok berdasarkan tahun ajaran dan nama proyek
        $kelompokData = Kelompok::where('tahun_ajaran', $tahunAjaran)
            ->where('nama_proyek', $namaProyek)
            ->get()
            ->groupBy('kelompok');

        Log::info('Data Kelompok:', ['kelompok' => $kelompokData]);


        $kelompokStats = $kelompokData->map(function ($kelompok) {
            $userIds = $kelompok->pluck('user_id'); // Semua user_id dalam kelompok
            Log::info('User IDs dalam Kelompok:', ['userIds' => $userIds]);
            $isSubmitted = $userIds->every(function ($userId) use ($userIds) {
                // Periksa apakah user ini sudah mengisi untuk semua anggota kelompok lain
                $filledCount = AnswersPeer::where('user_id', $userId)
                    ->whereIn('peer_id', $userIds->whereNotIn('id', [$userId]))
                    ->distinct('peer_id')
                    ->count();
                Log::info("User $userId Filled Count:", ['filledCount' => $filledCount]);
                return $filledCount === $userIds->count() - 1; // Semua anggota kecuali dirinya
            });
            return $isSubmitted;
        });

        // Hitung kelompok yang sudah lengkap
        $kelompokSudahLengkap = $kelompokStats->filter(fn($isSubmitted) => $isSubmitted)->count();
        $totalKelompok = $kelompokStats->count();

        $finalStats = [
            'totalKelompok' => $totalKelompok,
            'kelompokSudahLengkap' => $kelompokSudahLengkap,
            'kelompokBelumLengkap' => $totalKelompok - $kelompokSudahLengkap,
        ];
        Log::info('Final Stats:', $finalStats);
        return response()->json($finalStats);
    }

    public function getListAnswersKelompokPeer(Request $request)
    {
        $tahunAjaran = $request->query('tahun_ajaran');
        $namaProyek = $request->query('nama_proyek');

        if (!$tahunAjaran || !$namaProyek) {
            return response()->json([
                'success' => false,
                'message' => 'Tahun Ajaran atau Nama Proyek tidak ditemukan.'
            ], 400);
        }

        try {
            // Get unique groups for this academic year and project
            $groups = Kelompok::where('tahun_ajaran', $tahunAjaran)
                ->where('nama_proyek', $namaProyek)
                ->distinct('kelompok')
                ->pluck('kelompok');

            $results = $groups->map(function ($kelompok) use ($tahunAjaran, $namaProyek) {
                // Find all users in this group
                $groupUsers = Kelompok::where('kelompok', $kelompok)
                    ->where('tahun_ajaran', $tahunAjaran)
                    ->where('nama_proyek', $namaProyek)
                    ->pluck('user_id');

                // Count total members
                $totalMahasiswa = $groupUsers->count();

                // Count members who have completed assessments
                $totalFilled = AnswersPeer::whereIn('user_id', $groupUsers)
                    ->whereNotNull('status')
                    ->distinct('user_id')
                    ->count();

                return [
                    'nama_kelompok' => $kelompok,
                    'total_mahasiswa' => $totalMahasiswa,
                    'total_filled' => $totalFilled,
                    'user_ids' => $groupUsers->toArray(),
                    'user_id' => $groupUsers->first() ?? null
                ];
            });

            if ($results->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada data ditemukan.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $results
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching answers: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil data.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getListAnswersPeer(Request $request)
    {
        $tahunAjaran = $request->query('tahun_ajaran');
        $namaProyek = $request->query('nama_proyek');
        $kelompok = $request->query('kelompok');

        Log::info('Parameter diterima:', [
            'tahun_ajaran' => $tahunAjaran,
            'nama_proyek' => $namaProyek,
            'kelompok' => $kelompok,
        ]);

        if (!$tahunAjaran || !$namaProyek || !$kelompok) {
            return response()->json([
                'success' => false,
                'message' => 'Parameter tidak lengkap.',
            ], 400);
        }

        try {
            $answers = AnswersPeer::select('answerspeer.*', 'assessment.pertanyaan')
                ->join('assessment', 'answerspeer.question_id', '=', 'assessment.id')
                ->join('kelompok', 'answerspeer.user_id', '=', 'kelompok.user_id')
                ->where('assessment.tahun_ajaran', $tahunAjaran)
                ->where('assessment.nama_proyek', $namaProyek)
                ->where('kelompok.tahun_ajaran', $tahunAjaran)
                ->where('kelompok.nama_proyek', $namaProyek)
                ->where('kelompok.kelompok', $kelompok)
                ->with(['user', 'peer'])
                ->get();

            Log::info('Hasil query:', ['answers' => $answers->toArray()]);

            if ($answers->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada data ditemukan.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $answers,
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching answers: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil data.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getListAnswerPeer()
    {
        return Inertia::render('Dosen/ListAnswerPeer');
    }

    public function getListAnswersView()
    {
        return Inertia::render('Dosen/AnswersSelfAssessment');
    }

    public function getListAnswersPeerView()
    {
        return Inertia::render('Dosen/AnswersPeerAssessment');
    }

    public function searchByNip(Request $request)
    {
        $nim = $request->query('nip');
        $user = User::where('nip', $nim)->first();
        if ($user) {
            return response()->json(['user_id' => $user->id]);
        } else {
            return response()->json(['error' => 'User not found'], 404);
        }
    }

    public function getAnswerPeerDosen($questionId)
    {
        try {
            $answer = AnswersPeer::where('question_id', $questionId)
                ->where('user_id', auth()->id())
                ->first();

            return response()->json($answer);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    public function answeredPeersDosen(Request $request)
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|string|exists:users,id',
                'peer_id' => 'nullable|string',
                'question_id' => 'required|string|exists:assessment,id',
                'answer' => 'required|string',
                'score' => 'required|integer|min:1|max:5',
                'status' => 'required|string',
            ]);

            $peer_id = $validated['peer_id'] ?? null;

            $existingAnswer = AnswersPeer::where([
                'user_id' => $validated['user_id'],
                'peer_id' => $peer_id,
                'question_id' => $validated['question_id'],
            ])->first();

            if ($existingAnswer) {
                $existingAnswer->update([
                    'answer' => $validated['answer'],
                    'score' => $validated['score'],
                    'status' => $validated['status']
                ]);
                $answer = $existingAnswer;
            } else {
                $answer = AnswersPeer::create($validated);
            }

            return response()->json([
                'success' => true,
                'message' => 'Jawaban peer berhasil disimpan.',
                'data' => $answer,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error in AnswersPeer:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyimpan jawaban peer.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getQuestionsByProjectPeer(Request $request)
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
            ->where('assessment.type', 'peerAssessment')
            ->get();

        return response()->json($assessments);
    }

    public function AnswersPeerDosen(Request $request)
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|string|exists:users,id',
                'peer_id' => '-',
                'question_id' => 'required|string|exists:assessment,id',
                'answer' => 'required|string',
                'score' => 'required|integer|min:1|max:5',
                'status' => 'required|string',
            ]);

            $existingAnswer = AnswersPeer::where([
                'user_id' => $validated['user_id'],
                'peer_id' => $validated['peer_id'],
                'question_id' => $validated['question_id'],
            ])->first();

            if ($existingAnswer) {
                $existingAnswer->update([
                    'answer' => $validated['answer'],
                    'score' => $validated['score'],
                    'status' => $validated['status']
                ]);
                $answer = $existingAnswer;
            } else {
                $answer = AnswersPeer::create($validated);
            }

            return response()->json([
                'success' => true,
                'message' => 'Jawaban peer berhasil disimpan.',
                'data' => $answer,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error in AnswersPeer:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyimpan jawaban peer.',
                'error' => $e->getMessage()
            ], 500);
        }
    }





    public function getStatistics(Request $request)
    {
        try {
            $tahunAjaran = urldecode($request->query('tahun_ajaran'));
            $namaProyek = urldecode($request->query('nama_proyek'));

            Log::info('Input Statistics:', [
                'tahun_ajaran' => $tahunAjaran,
                'nama_proyek' => $namaProyek
            ]);

            // Cari kelompok untuk proyek spesifik
            $kelompok = Kelompok::where('tahun_ajaran', $tahunAjaran)
                ->where('nama_proyek', $namaProyek)
                ->get();

            Log::info('Kelompok Debug', [
                'total' => $kelompok->count(),
                'user_ids' => $kelompok->pluck('user_id')
            ]);

            // Cari jawaban yang terkait dengan proyek spesifik
            $usersAlreadyFilled = Answers::whereHas('question', function ($query) use ($tahunAjaran, $namaProyek) {
                $query->where('tahun_ajaran', $tahunAjaran)
                    ->where('nama_proyek', $namaProyek);
            })
                ->whereIn('user_id', $kelompok->pluck('user_id'))
                ->distinct('user_id')
                ->count();

            Log::info('Jawaban Debug', [
                'total_filled' => $usersAlreadyFilled
            ]);

            // Siapkan detail user yang belum mengisi
            $unsubmittedUsers = $kelompok->map(function ($item) use ($tahunAjaran, $namaProyek) {
                $isSubmitted = Answers::whereHas('question', function ($query) use ($tahunAjaran, $namaProyek) {
                    $query->where('tahun_ajaran', $tahunAjaran)
                        ->where('nama_proyek', $namaProyek);
                })
                    ->where('user_id', $item->user_id)
                    ->exists();

                return [
                    'index' => $item->id,
                    'userName' => $item->user->name,
                    'status' => $isSubmitted ? 'submitted' : 'unsubmitted'
                ];
            });

            return response()->json([
                'totalKeseluruhan' => $kelompok->count(),
                'totalSudahMengisi' => $usersAlreadyFilled,
                'unsubmittedUsers' => $unsubmittedUsers
            ]);
        } catch (\Exception $e) {
            Log::error('Statistik Error: ' . $e->getMessage() . ' Trace: ' . $e->getTraceAsString());

            return response()->json([
                'error' => 'Fatal Error',
                'message' => $e->getMessage()
            ], 500);
        }
    }


    public function getDetails(Request $request)
    {
        $validated = $request->validate([
            'userName' => 'required|string',
            'tahun_ajaran' => 'required|string',
            'nama_proyek' => 'required|string',
        ]);

        return Inertia::render('Dosen/AnswerDetailSelf')->with([
            'userName' => $validated['userName'],
            'tahunAjaran' => $validated['tahun_ajaran'],
            'namaProyek' => $validated['nama_proyek'],
        ]);
    }

    public function getDetailsAnswer(Request $request)
    {
        $validated = $request->validate([
            'userName' => 'required|string',
            'tahun_ajaran' => 'required|string',
            'nama_proyek' => 'required|string',
        ]);

        $user = DB::table('users')->where('name', $validated['userName'])->first();

        if (!$user) {
            return response()->json(['error' => 'Pengguna tidak ditemukan'], 404);
        }

        $answers = Answers::with('question')
            ->where('user_id', $user->id)
            ->whereHas('question', function ($query) use ($validated) {
                $query->where('tahun_ajaran', $validated['tahun_ajaran'])
                    ->where('nama_proyek', $validated['nama_proyek']);
            })
            ->get();

        if ($answers->isEmpty()) {
            return response()->json(['message' => 'Jawaban tidak ditemukan untuk pengguna ini'], 404);
        }

        return response()->json([
            'answers' => $answers->map(function ($answer) {
                return [
                    'pertanyaan' => $answer->question->pertanyaan,
                    'jawaban' => $answer->answer,
                    'skor' => $answer->score,
                ];
            }),
        ]);
    }
}
