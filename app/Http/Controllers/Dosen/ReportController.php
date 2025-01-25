<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use App\Models\Kelompok;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Project;
use App\Models\Answers;
use App\Models\AnswersPeer;
use App\Models\User;
use App\Models\Assessment;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    public function report()
    {
        return Inertia::render('Dosen/Report');
    }

    public function getDropdownOptions(Request $request): JsonResponse
    {
        // Ambil data tahun ajaran yang unik
        $tahunAjaranOptions = Project::select('tahun_ajaran')->distinct()->pluck('tahun_ajaran');

        // Jika ada tahun ajaran yang dipilih, ambil nama proyek berdasarkan tahun ajaran tersebut
        $namaProyekOptions = Project::select('nama_proyek', 'tahun_ajaran')
            ->when($request->tahun_ajaran, function ($query, $tahunAjaran) {
                return $query->where('tahun_ajaran', $tahunAjaran);
            })
            ->distinct()
            ->get();

        // Gabungkan data tahun ajaran dan nama proyek
        $combinedOptions = [];
        foreach ($tahunAjaranOptions as $tahun) {
            foreach ($namaProyekOptions->where('tahun_ajaran', $tahun) as $proyek) {
                $combinedOptions[] = [
                    'value' => "{$tahun} - {$proyek->nama_proyek}",
                    'label' => "{$tahun} - {$proyek->nama_proyek}", // Atau bisa menggunakan 'value' jika hanya ingin memilih tahun ajaran dan nama proyek
                    'tahunAjaran' => $tahun,
                    'namaProyek' => $proyek->nama_proyek,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'options' => $combinedOptions,
        ]);
    }

    public function getKelompokReport(Request $request): JsonResponse
    {
        // Validasi parameter yang diterima
        $request->validate([
            'tahun_ajaran' => 'required|string',
            'nama_proyek' => 'required|string',
        ]);

        // Ambil data kelompok berdasarkan tahun ajaran dan nama proyek
        $kelompokData = Kelompok::where('tahun_ajaran', $request->tahun_ajaran)
            ->where('nama_proyek', $request->nama_proyek)
            ->with('user') // Eager load relasi user
            ->get(['id', 'kelompok', 'user_id']); // Ambil kolom id, kelompok, dan user_id

        // Jika tidak ada data, beri respon kosong
        if ($kelompokData->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada kelompok yang ditemukan.',
            ]);
        }

        // Group data berdasarkan nama kelompok (kelompok)
        $groupedKelompok = $kelompokData->groupBy('kelompok');

        // Format data kelompok dan merge jika ada kelompok yang sama
        $kelompokList = $groupedKelompok->map(function ($group, $kelompokName) {
            // Ambil ID pertama dari kelompok
            $kelompok = [
                'id' => $group->pluck('id')->first(),  // ID kelompok
                'nama_kelompok' => $kelompokName,  // Nama kelompok
                'anggota' => [], // Menyimpan anggota kelompok
            ];

            // Ambil semua anggota dengan user_id terkait
            foreach ($group as $item) {
                // Menambahkan nama anggota berdasarkan user_id
                $kelompok['anggota'][] = [
                    'user_id' => $item->user_id,
                    'name' => $item->user->name,  // Ambil nama pengguna dari relasi user
                ];
            }

            return $kelompok;
        });

        return response()->json([
            'success' => true,
            'kelompok' => $kelompokList,
        ]);
    }

    public function getScoreKelompok(Request $request)
    {
        // Ambil parameter dari query string
        $tahunAjaran = $request->query('tahun_ajaran');
        $namaProyek = $request->query('nama_proyek');
        $kelompok = $request->query('kelompok');

        // Log data untuk melihat apakah parameter diterima
        Log::info('Data diterima di controller:', [
            'tahun_ajaran' => $tahunAjaran,
            'nama_proyek' => $namaProyek,
            'kelompok' => $kelompok,
        ]);

        // Cek apakah parameter ada dan ambil data kelompok
        $kelompokDetail = Kelompok::where('tahun_ajaran', $tahunAjaran)
            ->where('nama_proyek', $namaProyek)
            ->where('kelompok', $kelompok)
            ->first();

        return Inertia::render('Dosen/ReportScore', [
            'tahunAjaran' => $tahunAjaran,
            'namaProyek' => $namaProyek,
            'kelompok' => $kelompok,
        ]);
    }

    public function getKelompokAnswers(Request $request)
    {
        $tahunAjaran = $request->input('tahun_ajaran');
        $namaProyek = $request->input('nama_proyek');
        $kelompok = $request->input('kelompok');

        try {
            // Retrieve user_ids for the specific group
            $userIds = Kelompok::where('tahun_ajaran', $tahunAjaran)
                ->where('nama_proyek', $namaProyek)
                ->where('kelompok', $kelompok)
                ->pluck('user_id');

            if ($userIds->isEmpty()) {
                return response()->json([], 200);
            }

            // Get user names based on user_ids
            $userNames = User::whereIn('id', $userIds)->pluck('name', 'id');

            // Process each user's assessments
            $userResults = $userIds->mapWithKeys(function ($userId) use ($tahunAjaran, $namaProyek, $userNames, $kelompok) {
                // Self Assessments
                $selfAssessments = Assessment::where('tahun_ajaran', $tahunAjaran)
                    ->where('nama_proyek', $namaProyek)
                    ->where('type', 'selfAssessment')
                    ->get();

                $selfAspekKriteriaAnalysis = $this->analyzeAssessments($selfAssessments, $userId, 'selfAssessment', $tahunAjaran, $namaProyek);

                // Peer Assessments
                $peerAssessments = Assessment::where('tahun_ajaran', $tahunAjaran)
                    ->where('nama_proyek', $namaProyek)
                    ->where('type', 'peerAssessment')
                    ->get();

                $peerAspekKriteriaAnalysis = $this->analyzeAssessments($peerAssessments, $userId, 'peerAssessment', $tahunAjaran, $namaProyek);

                // Peer Evaluations
                $peerEvaluations = AnswersPeer::select(
                    'answerspeer.*',
                    'assessment.pertanyaan',
                    'assessment.aspek',
                    'assessment.kriteria'
                )
                    ->join('assessment', 'answerspeer.question_id', '=', 'assessment.id')
                    ->join('kelompok', 'answerspeer.user_id', '=', 'kelompok.user_id')
                    ->where('assessment.tahun_ajaran', $tahunAjaran)
                    ->where('assessment.nama_proyek', $namaProyek)
                    ->where('answerspeer.peer_id', $userId)
                    ->where('kelompok.tahun_ajaran', $tahunAjaran)
                    ->where('kelompok.nama_proyek', $namaProyek)
                    ->where('kelompok.kelompok', $kelompok)
                    ->get()
                    ->groupBy(function ($item) {
                        return $item->aspek . '_' . $item->kriteria;
                    })
                    ->map(function ($answers, $aspekKriteria) use ($userNames) {
                        list($aspek, $kriteria) = explode('_', $aspekKriteria);

                        $filteredAnswers = $answers->filter(function ($answer) {
                            return $answer->score !== null;
                        });

                        if ($filteredAnswers->isEmpty()) {
                            return null;
                        }

                        return [
                            'aspek' => $aspek,
                            'kriteria' => $kriteria,
                            'total_score' => $filteredAnswers->avg('score'),
                            'total_answers' => $filteredAnswers->count(),
                            'evaluated_by' => $filteredAnswers->mapWithKeys(function ($answer) use ($userNames) {
                                return [$answer->user_id => [
                                    'name' => $userNames[$answer->user_id] ?? 'Tidak dikenal',
                                    'total_score' => $answer->score,
                                    'answers' => [[
                                        'question_id' => $answer->question_id,
                                        'pertanyaan' => $answer->pertanyaan,
                                        'score' => $answer->score,
                                        'answer' => $answer->answer,
                                        'aspek' => $answer->aspek,
                                        'kriteria' => $answer->kriteria,
                                    ]]
                                ]];
                            })
                        ];
                    })->filter()
                    ->values();

                return [
                    $userId => [
                        'user_id' => $userId,
                        'name' => $userNames[$userId] ?? 'Tidak dikenal',
                        'self_assessment' => $selfAspekKriteriaAnalysis->values(),
                        'peer_assessment' => $peerAspekKriteriaAnalysis->values(),
                        'evaluated_by_peers' => $peerEvaluations,
                    ],
                ];
            });

            return response()->json($userResults);
        } catch (\Exception $e) {
            Log::error('Error in getKelompokAnswers: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memproses data.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function analyzeAssessments($assessments, $userId, $assessmentType, $tahunAjaran, $namaProyek)
    {
        // Filter assessments by tahun_ajaran and nama_proyek
        $filteredAssessments = $assessments->filter(function ($assessment) use ($tahunAjaran, $namaProyek) {
            return $assessment->tahun_ajaran === $tahunAjaran && $assessment->nama_proyek === $namaProyek;
        });

        if ($filteredAssessments->isEmpty()) {
            return collect([]);
        }

        return $filteredAssessments->groupBy(function ($assessment) {
            return $assessment->aspek . '_' . $assessment->kriteria;
        })->map(function ($groupAssessments) use ($userId, $assessmentType) {
            $questionIds = $groupAssessments->pluck('id');

            $answers = $assessmentType === 'selfAssessment'
                ? Answers::whereIn('question_id', $questionIds)
                ->where('user_id', $userId)
                ->get()
                : AnswersPeer::whereIn('question_id', $questionIds)
                ->where('peer_id', $userId)
                ->get();

            return [
                'aspek' => $groupAssessments->first()->aspek,
                'kriteria' => $groupAssessments->first()->kriteria,
                'total_score' => $answers->avg('score'),
                'total_answers' => $answers->count(),
                'questions' => $groupAssessments->map(function ($assessment) use ($answers) {
                    $relatedAnswer = $answers->where('question_id', $assessment->id)->first();
                    return [
                        'question_id' => $assessment->id,
                        'pertanyaan' => $assessment->pertanyaan,
                        'score' => $relatedAnswer ? $relatedAnswer->score : null,
                        'answer' => $relatedAnswer ? $relatedAnswer->answer : null
                    ];
                })
            ];
        });
    }
}
