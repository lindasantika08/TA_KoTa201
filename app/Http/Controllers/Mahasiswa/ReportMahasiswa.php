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

            // Get the mahasiswa record associated with the user
            $mahasiswa = Mahasiswa::where('user_id', $userId)->first();

            if (!$mahasiswa) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mahasiswa not found'
                ], 404);
            }

            // Get groups associated with the mahasiswa, including project data
            $groups = Group::with(['project' => function ($query) {
                $query->select('id', 'project_name', 'batch_year', 'status', 'semester');
            }])
                ->where('mahasiswa_id', $mahasiswa->id)
                ->get();

            // Format the response to match your frontend expectations
            $projects = $groups->map(function ($group) {
                return [
                    'id' => $group->id,
                    'nama_proyek' => $group->project->project_name,
                    'tahun_ajaran' => $group->project->batch_year,
                    'nama_kelompok' => $group->group, // Using the 'group' field from your model
                    'status' => $group->project->status ?? 'Tidak Diketahui',
                    // Add semester if needed in frontend
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
        // Validasi input request untuk memastikan data yang diterima benar
        $validatedData = $request->validate([
            'tahun_ajaran' => 'required|string|max:10',
            'nama_proyek' => 'required|string|max:255',
            'kelompok' => 'required|string|max:10',
        ]);

        // Ambil parameter dari request
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
            'projectName' => $project->project_name,  // Add this line
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

        // Add logging
        Log::info('Request parameters:', [
            'batch_year' => $batchYear,
            'project_id' => $projectId,
            'kelompok' => $kelompok,
            'user_id' => $userId
        ]);

        try {
            // Get mahasiswa record for the logged-in user
            $mahasiswa = Mahasiswa::where('user_id', $userId)->first();

            // Add logging
            Log::info('Mahasiswa found:', ['mahasiswa' => $mahasiswa]);

            if (!$mahasiswa) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Mahasiswa not found'
                ], 404);
            }

            // Get user's group for this project
            $group = Group::where('batch_year', $batchYear)
                ->where('project_id', $projectId)
                ->where('mahasiswa_id', $mahasiswa->id)
                ->first();

            // Add logging
            Log::info('Group found:', ['group' => $group]);

            if (!$group) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Group not found'
                ], 404);
            }

            // Get assessments for this project with logging
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
                $projectId
            );

            // Peer Assessments with logging
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
                $projectId
            );

            // Prepare user results
            $userResults = [
                'user_id' => $userId,
                'name' => Auth::user()->name ?? 'Tidak dikenal',  // Correctly retrieve user name
                'kelompok' => $group->group,  // Add group info here
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

    private function analyzeAssessments($assessments, $mahasiswaId, $assessmentType, $batchYear, $projectId)
    {
        Log::info('Starting analyzeAssessments', [
            'assessmentType' => $assessmentType,
            'mahasiswaId' => $mahasiswaId,
            'assessmentCount' => $assessments->count()
        ]);

        // Filter assessments by batch_year and project_id
        $filteredAssessments = $assessments->filter(function ($assessment) use ($batchYear, $projectId) {
            return $assessment->batch_year === $batchYear && $assessment->project_id === $projectId;
        });

        Log::info('Filtered assessments count:', ['count' => $filteredAssessments->count()]);

        if ($filteredAssessments->isEmpty()) {
            Log::warning('No assessments found after filtering');
            return collect([]);
        }

        $result = $filteredAssessments->groupBy(function ($assessment) {
            if (!$assessment->typeCriteria) {
                Log::error('typeCriteria relation not found for assessment:', ['assessment_id' => $assessment->id]);
                return null;
            }
            return $assessment->typeCriteria->aspect . '_' . $assessment->typeCriteria->criteria;
        })->map(function ($groupAssessments) use ($mahasiswaId, $assessmentType) {
            $questionIds = $groupAssessments->pluck('id');

            // Get answers based on assessment type
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

            // Get peer names
            $peerNames = $groupAssessments->map(function ($assessment) {
                return $assessment->peer->name ?? 'Unknown Peer'; // Ensure peer name is available
            })->unique()->values();

            return [
                'aspek' => $typeCriteria->aspect,
                'kriteria' => $typeCriteria->criteria,
                'total_score' => $answers->avg('score'),
                'total_answers' => $answers->count(),
                'questions' => $groupAssessments->map(function ($assessment) use ($answers) {
                    $relatedAnswer = $answers->where('question_id', $assessment->id)->first();
                    return [
                        'question_id' => $assessment->id,
                        'pertanyaan' => $assessment->question,
                        'score' => $relatedAnswer ? $relatedAnswer->score : null,
                        'answer' => $relatedAnswer ? $relatedAnswer->answer : null
                    ];
                }),
                'peer_names' => $peerNames // Include peer names in the result
            ];
        });

        Log::info('Analysis result count:', ['count' => $result->count()]);

        return $result;
    }
}
