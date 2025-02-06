<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Mahasiswa;
use App\Models\Group;
use App\Models\Assessment;
use App\Models\Project;
use App\Models\Answers;
use App\Models\AnswersPeer;
use Illuminate\Support\Facades\Auth;

class FeedbackMahasiswa extends Controller
{
    public function feedbackMahasiswa()
    {
        return Inertia::render('Mahasiswa/FeedbackMahasiswa');
    }

    public function getStudentAssessmentsStatus()
    {
        $user = Auth::user();
        $mahasiswa = Mahasiswa::where('user_id', $user->id)->first();

        if (!$mahasiswa) {
            return response()->json([
                'status' => 'error',
                'message' => 'Mahasiswa not found'
            ], 404);
        }

        // Get all groups (projects) for the student
        $userGroups = Group::where('mahasiswa_id', $mahasiswa->id)
            ->with('project')
            ->get();

        if ($userGroups->isEmpty()) {
            return response()->json([
                'status' => 'success',
                'projects' => []
            ]);
        }

        $projectStatuses = $userGroups->map(function ($group) use ($mahasiswa) {
            $project = $group->project;

            // Get peer group members
            $groupPeers = Group::where('project_id', $project->id)
                ->where('group', $group->group)
                ->where('mahasiswa_id', '!=', $mahasiswa->id)
                ->pluck('mahasiswa_id');

            // Count total questions for each assessment type
            $selfAssessmentQuestions = Assessment::where('type', 'selfAssessment')
                ->where('project_id', $project->id)
                ->count();

            $peerAssessmentQuestions = Assessment::where('type', 'peerAssessment')
                ->where('project_id', $project->id)
                ->count();

            // Count completed self assessments
            $completedSelfAssessments = Answers::where('mahasiswa_id', $mahasiswa->id)
                ->whereHas('question', function ($query) use ($project) {
                    $query->where('type', 'selfAssessment')
                        ->where('project_id', $project->id);
                })
                ->count();

            // Count completed peer assessments for each peer
            $totalPeerAssessments = 0;
            foreach ($groupPeers as $peerId) {
                $completedForPeer = AnswersPeer::where('mahasiswa_id', $mahasiswa->id)
                    ->where('peer_id', $peerId)
                    ->whereHas('question', function ($query) use ($project) {
                        $query->where('type', 'peerAssessment')
                            ->where('project_id', $project->id);
                    })
                    ->count();
                $totalPeerAssessments += $completedForPeer;
            }

            // Calculate expected total peer assessments
            $expectedTotalPeerAssessments = $peerAssessmentQuestions * count($groupPeers);

            // Determine completion status
            $selfAssessmentStatus = ($selfAssessmentQuestions > 0 && $completedSelfAssessments >= $selfAssessmentQuestions)
                ? 'COMPLETED'
                : 'PENDING';

            $peerAssessmentStatus = ($expectedTotalPeerAssessments > 0 && $totalPeerAssessments >= $expectedTotalPeerAssessments)
                ? 'COMPLETED'
                : 'PENDING';

            return [
                'project_name' => $project->project_name,
                'project_id' => $project->id,
                'group_name' => $group->group,
                'selfAssessmentStatus' => $selfAssessmentStatus,
                'peerAssessmentStatus' => $peerAssessmentStatus,
                'selfAssessment' => [
                    'completed' => $completedSelfAssessments,
                    'total' => $selfAssessmentQuestions
                ],
                'peerAssessment' => [
                    'completed' => $totalPeerAssessments,
                    'total' => $expectedTotalPeerAssessments,
                    'peers_count' => count($groupPeers)
                ]
            ];
        });

        // Calculate overall completion status
        $allCompleted = $projectStatuses->every(function ($status) {
            return $status['selfAssessmentStatus'] === 'COMPLETED'
                && $status['peerAssessmentStatus'] === 'COMPLETED';
        });

        return response()->json([
            'status' => 'success',
            'overall_status' => $allCompleted ? 'COMPLETED' : 'PENDING',
            'projects' => $projectStatuses
        ]);
    }

    public function getFeedbackDetailView(Request $request)
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

        return Inertia::render('Mahasiswa/FeedbackMahasiswaDetail', [
            'batchYear' => $batchYear,
            'projectId' => $project->id,
            'projectName' => $project->project_name,  // Add this line
            'kelompok' => $kelompok,
            'userName' => auth()->user()->name,
        ]);
    }

    public function getGroupMembers($projectId)
    {
        try {
            $user = auth()->user();

            if (!$user || !$user->isMahasiswa()) {
                return response()->json(['message' => 'Access denied'], 403);
            }

            $mahasiswa = Mahasiswa::where('user_id', $user->id)->first();

            if (!$mahasiswa) {
                return response()->json(['message' => 'Mahasiswa record not found'], 404);
            }

            $currentUserGroup = Group::where('project_id', $projectId)
                ->where('mahasiswa_id', $mahasiswa->id)
                ->first();

            if (!$currentUserGroup) {
                return response()->json(['message' => 'Group not found'], 404);
            }

            $groupMembers = Group::where('project_id', $projectId)
                ->where('group', $currentUserGroup->group)
                ->where('mahasiswa_id', '!=', $mahasiswa->id)
                ->with(['mahasiswa.user'])
                ->get()
                ->map(function ($group) {
                    return [
                        'id' => $group->mahasiswa->id,
                        'name' => $group->mahasiswa->user->name,
                        'nim' => $group->mahasiswa->nim
                    ];
                });

            return response()->json($groupMembers);
        } catch (\Exception $e) {
            \Log::error('Error in getGroupMembers:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Internal server error'
            ], 500);
        }
    }
}
