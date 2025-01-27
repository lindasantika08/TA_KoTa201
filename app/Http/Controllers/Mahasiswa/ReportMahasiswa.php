<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Kelompok;
use App\Models\Answers;
use App\Models\AnswersPeer;
use App\Models\Assessment;
use App\Models\User;

class ReportMahasiswa extends Controller
{
    public function reportMahasiswa()
    {

        return Inertia::render('Mahasiswa/ReportMahasiswa');
    }

    public function getReportScoreView()
    {
        return Inertia::render('Mahasiswa/ReportScoreMahasiswa');
    }

    public function getProjects()
    {
        try {
            $userId = Auth::id();

            // Query dengan join antara tabel kelompok dan project
            $kelompokList = DB::table('kelompok')
                ->join('project', function ($join) {
                    $join->on('kelompok.tahun_ajaran', '=', 'project.tahun_ajaran')
                        ->on('kelompok.nama_proyek', '=', 'project.nama_proyek');
                })
                ->where('kelompok.user_id', $userId)
                ->select(
                    'kelompok.id',
                    'kelompok.nama_proyek',
                    'kelompok.tahun_ajaran',
                    'kelompok.kelompok',
                    'project.status'
                )
                ->get();

            // Format data menjadi sesuai output
            $projects = $kelompokList->map(function ($kelompok) {
                return [
                    'id' => $kelompok->id,
                    'nama_proyek' => $kelompok->nama_proyek,
                    'tahun_ajaran' => $kelompok->tahun_ajaran,
                    'nama_kelompok' => $kelompok->kelompok,
                    'status' => $kelompok->status ?? 'Tidak Diketahui',
                ];
            });

            return response()->json([
                'success' => true,
                'projects' => $projects
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching projects: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getProjectScoreDetails(Request $request)
    {
        $tahunAjaran = $request->input('tahun_ajaran');
        $namaProyek = $request->input('nama_proyek');
        $userId = Auth::id(); // Get logged in user ID

        try {
            // Get user's group
            $kelompok = Kelompok::where('tahun_ajaran', $tahunAjaran)
                ->where('nama_proyek', $namaProyek)
                ->where('user_id', $userId)
                ->first();

            if (!$kelompok) {
                return response()->json([], 200);
            }

            // Get user name
            $userName = User::where('id', $userId)->value('name');

            // Self Assessments
            $selfAssessments = Assessment::where('tahun_ajaran', $tahunAjaran)
                ->where('nama_proyek', $namaProyek)
                ->where('type', 'selfAssessment')
                ->get();

            $selfAspekKriteriaAnalysis = $this->analyzeAssessments(
                $selfAssessments,
                $userId,
                'selfAssessment',
                $tahunAjaran,
                $namaProyek
            );

            // Peer Assessments
            $peerAssessments = Assessment::where('tahun_ajaran', $tahunAjaran)
                ->where('nama_proyek', $namaProyek)
                ->where('type', 'peerAssessment')
                ->get();

            $peerAspekKriteriaAnalysis = $this->analyzeAssessments(
                $peerAssessments,
                $userId,
                'peerAssessment',
                $tahunAjaran,
                $namaProyek
            );

            // Prepare user results
            $userResults = [
                'user_id' => $userId,
                'name' => $userName ?? 'Tidak dikenal',
                'kelompok' => $kelompok->kelompok,
                'self_assessment' => $selfAspekKriteriaAnalysis->values(),
                'peer_assessment' => $peerAspekKriteriaAnalysis->values(),
            ];

            return response()->json([
                'status' => 'success',
                'data' => $userResults
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat memproses data.',
                'error' => $e->getMessage()
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
