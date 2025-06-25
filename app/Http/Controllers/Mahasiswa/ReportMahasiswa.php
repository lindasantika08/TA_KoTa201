<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Project;
use App\Models\Group;
use App\Models\Answers;
use App\Models\AnswersPeer;
use App\Models\Report;
use App\Models\Assessment;
use App\Models\User;
use App\Models\Mahasiswa;

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

            $mahasiswa = Mahasiswa::where('user_id', $userId)->first();

            if (!$mahasiswa) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mahasiswa not found'
                ], 404);
            }

            $groups = Group::with(['project' => function ($query) {
                $query->select('id', 'project_name', 'batch_year', 'status', 'semester');
            }])
                ->where('mahasiswa_id', $mahasiswa->id)
                ->get();

            $projects = $groups->map(function ($group) {
                return [
                    'id' => $group->id,
                    'nama_proyek' => $group->project->project_name,
                    'tahun_ajaran' => $group->project->batch_year,
                    'nama_kelompok' => $group->group,
                    'status' => $group->project->status ?? 'Tidak Diketahui',
                    'semester' => $group->project->semester
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

    public function getProjectScoreDetailsView(Request $request)
    {
        $validatedData = $request->validate([
            'tahun_ajaran' => 'required|string|max:10',
            'nama_proyek' => 'required|string|max:255',
            'kelompok' => 'required|string|max:50',
        ]);

        $batchYear = $validatedData['tahun_ajaran'];
        $projectName = $validatedData['nama_proyek'];
        $kelompok = $validatedData['kelompok'];

        $project = Project::where('project_name', $projectName)
            ->where('batch_year', $batchYear)
            ->firstOrFail();

        $group = Group::where('group', $kelompok)
            ->where('project_id', $project->id)
            ->where('batch_year', $batchYear)
            ->first();

        if (!$group) {
            return redirect()->route('mahasiswa.dashboard')->withErrors('Kelompok tidak ditemukan untuk proyek ini.');
        }

        return Inertia::render('Mahasiswa/ReportScoreMahasiswa', [
            'batchYear' => $batchYear,
            'projectId' => $project->id,
            'projectName' => $project->project_name,
            'kelompok' => $kelompok,
            'userName' => auth()->user()->name,
        ]);
    }

    public function getProjectScoreDetails(Request $request)
    {
        $batchYear = $request->input('batch_year');
        $projectId = $request->input('project_id');
        $kelompok = $request->input('kelompok');
        $userId = Auth::id();

        Log::info('Request parameters:', [
            'batch_year' => $batchYear,
            'project_id' => $projectId,
            'kelompok' => $kelompok,
            'user_id' => $userId
        ]);

        try {
            $mahasiswa = Mahasiswa::where('user_id', $userId)->first();

            Log::info('Mahasiswa found:', ['mahasiswa' => $mahasiswa]);

            if (!$mahasiswa) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Mahasiswa not found'
                ], 404);
            }

            $group = Group::where('batch_year', $batchYear)
                ->where('project_id', $projectId)
                ->where('mahasiswa_id', $mahasiswa->id)
                ->first();

            Log::info('Group found:', ['group' => $group]);

            if (!$group) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Group not found'
                ], 404);
            }

            $selfAssessments = Assessment::where('batch_year', $batchYear)
                ->where('project_id', $projectId)
                ->where('type', 'selfAssessment')
                ->get();

            Log::info('Self Assessments found:', ['count' => $selfAssessments->count()]);

            $selfAspekKriteriaAnalysis = $this->analyzeAssessments(
                $selfAssessments,
                $mahasiswa->id,
                'selfAssessment',
                $batchYear,
                $projectId,
                $group->id
            );

            $peerAssessments = Assessment::where('batch_year', $batchYear)
                ->where('project_id', $projectId)
                ->where('type', 'peerAssessment')
                ->get();

            Log::info('Peer Assessments found:', ['count' => $peerAssessments->count()]);

            $peerAspekKriteriaAnalysis = $this->analyzeAssessments(
                $peerAssessments,
                $mahasiswa->id,
                'peerAssessment',
                $batchYear,
                $projectId,
                $group->id
            );

            $userResults = [
                'user_id' => $userId,
                'name' => Auth::user()->name ?? 'Tidak dikenal',
                'kelompok' => $group->group,
                'self_assessment' => $selfAspekKriteriaAnalysis->values(),
                'peer_assessment' => $peerAspekKriteriaAnalysis->values(),
            ];

            Log::info('Final response:', ['userResults' => $userResults]);

            return response()->json([
                'status' => 'success',
                'data' => $userResults
            ]);
        } catch (\Exception $e) {
            Log::error('Error in getProjectScoreDetails:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat memproses data.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function analyzeAssessments($assessments, $mahasiswaId, $assessmentType, $batchYear, $projectId, $groupId)
    {
        Log::info('Starting analyzeAssessments', [
            'assessmentType' => $assessmentType,
            'mahasiswaId' => $mahasiswaId,
            'assessmentCount' => $assessments->count()
        ]);

        $filteredAssessments = $assessments->filter(function ($assessment) use ($batchYear, $projectId) {
            return $assessment->batch_year === $batchYear && $assessment->project_id === $projectId;
        });

        Log::info('Filtered assessments count:', ['count' => $filteredAssessments->count()]);

        if ($filteredAssessments->isEmpty()) {
            Log::warning('No assessments found after filtering');
            return collect([]);
        }

        $reports = Report::where('project_id', $projectId)
            ->where('group_id', $groupId)
            ->where('mahasiswa_id', $mahasiswaId)
            ->get();

        Log::info('Reports found:', ['count' => $reports->count()]);

        $result = $filteredAssessments->groupBy(function ($assessment) {
            if (!$assessment->typeCriteria) {
                Log::error('typeCriteria relation not found for assessment:', ['assessment_id' => $assessment->id]);
                return null;
            }
            return $assessment->typeCriteria->aspect . '_' . $assessment->typeCriteria->criteria;
        })->map(function ($groupAssessments) use ($mahasiswaId, $assessmentType, $reports) {
            $questionIds = $groupAssessments->pluck('id');

            $answers = $assessmentType === 'selfAssessment'
                ? Answers::whereIn('question_id', $questionIds)
                ->where('mahasiswa_id', $mahasiswaId)
                ->get()
                : AnswersPeer::whereIn('question_id', $questionIds)
                ->where('peer_id', $mahasiswaId)
                ->get();

            Log::info('Answers found for group:', [
                'questionIds' => $questionIds,
                'answersCount' => $answers->count()
            ]);

            $typeCriteria = $groupAssessments->first()->typeCriteria;

            $peerNames = $groupAssessments->map(function ($assessment) {
                return $assessment->peer->name ?? 'Unknown Peer';
            })->unique()->values();

            $questions = $groupAssessments->map(function ($assessment) use ($answers, $reports, $mahasiswaId, $assessmentType) {
                $relatedAnswer = $answers->where('question_id', $assessment->id)->first();
                $relatedReport = $reports->where('question_id', $assessment->id)
                    ->where('typeCriteria_id', $assessment->typeCriteria->id)
                    ->first();

                // Ambil final_score jika ada, jika tidak pakai skor_self/skor_peer
                $finalScoreSelf = null;
                $finalScorePeer = null;
                if ($assessmentType === 'selfAssessment' && $relatedReport) {
                    $finalScoreSelf = $relatedReport->final_score_self ?? $relatedReport->skor_self ?? null;
                }
                if ($assessmentType === 'peerAssessment' && $relatedReport) {
                    $finalScorePeer = $relatedReport->final_score_peer ?? $relatedReport->skor_peer ?? null;
                }

                return [
                    'question_id' => $assessment->id,
                    'pertanyaan' => $assessment->question,
                    'score' => $relatedAnswer ? $relatedAnswer->score : null,
                    'answer' => $relatedAnswer ? $relatedAnswer->answer : null,
                    'final_score_self' => $finalScoreSelf,
                    'final_score_peer' => $finalScorePeer
                ];
            });

            $totalScoreSelf = null;
            $totalScorePeer = null;

            if ($assessmentType === 'selfAssessment') {
                $validScores = $questions->pluck('final_score_self')->filter()->values();
                $totalScoreSelf = $validScores->count() > 0 ? $validScores->avg() : null;
            } else { // peerAssessment
                $validScores = $questions->pluck('final_score_peer')->filter()->values();
                $totalScorePeer = $validScores->count() > 0 ? $validScores->avg() : null;
            }

            $totalScore = $answers->avg('score');

            return [
                'aspek' => $typeCriteria->aspect,
                'kriteria' => $typeCriteria->criteria,
                'total_score' => $totalScore,
                'total_score_self' => $totalScoreSelf,
                'total_score_peer' => $totalScorePeer,
                'total_answers' => $answers->count(),
                'questions' => $questions,
                'peer_names' => $peerNames
            ];
        });

        Log::info('Analysis result count:', ['count' => $result->count()]);

        return $result;
    }
}
