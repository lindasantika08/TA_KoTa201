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
use App\Models\Feedback;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;


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

            // Log debugging
            Log::info("Mahasiswa ditemukan: {$mahasiswa->id}, Group: {$currentUserGroup->id}");

            // Ambil semua anggota dalam kelompok yang sama
            $groupMembers = Group::where('project_id', $projectId)
                ->where('group', $currentUserGroup->group)
                ->where('mahasiswa_id', '!=', $mahasiswa->id)
                ->with(['mahasiswa.user', 'project'])
                ->get();

            // Log debugging
            Log::info("Total anggota group ditemukan: " . count($groupMembers));

            // Periksa apakah tabel feedback memiliki kolom group_id
            $submittedFeedbacks = Feedback::where('group_id', $currentUserGroup->id)
                ->pluck('peer_id')
                ->toArray();


            Log::info("Total feedback diberikan: " . count($submittedFeedbacks));

            // Filter anggota yang belum diberi feedback
            $filteredMembers = $groupMembers->map(function ($group) use ($submittedFeedbacks) {
                return [
                    'id' => $group->mahasiswa->id,
                    'name' => $group->mahasiswa->user->name,
                    'nim' => $group->mahasiswa->nim,
                    'feedbackSubmitted' => in_array($group->mahasiswa->id, $submittedFeedbacks),
                    'project_name' => $group->project->name ?? null,
                    'batch_year' => $group->batch_year
                ];
            })->filter(function ($member) {
                return !$member['feedbackSubmitted'];
            })->values();

            return response()->json($filteredMembers);
        } catch (\Exception $e) {
            Log::error('Error in getGroupMembers:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'Internal server error'], 500);
        }
    }




    public function saveFeedbackMahasiswa(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'recipientId' => 'required|exists:mahasiswa,id',
            'message' => 'required|string',
            'batchYear' => 'required|string',
            'projectName' => 'required|string',
            'projectId' => 'required|exists:project,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $mahasiswa = Mahasiswa::where('user_id', Auth::id())->first();
        if (!$mahasiswa) {
            return response()->json([
                'status' => 'error',
                'message' => 'Mahasiswa not found'
            ], 404);
        }

        // Find the project
        $project = Project::where('batch_year', $request->batchYear)
            ->where('project_name', $request->projectName)
            ->first();

        if (!$project) {
            return response()->json([
                'status' => 'error',
                'message' => 'Project not found'
            ], 404);
        }

        // Find the group for this user in the specified project
        $group = Group::where('project_id', $project->id)
            ->where('mahasiswa_id', $mahasiswa->id)
            ->where('batch_year', $request->batchYear)
            ->first();

        if (!$group) {
            return response()->json([
                'status' => 'error',
                'message' => 'Group not found for this user in the specified project'
            ], 404);
        }

        $recipient = Mahasiswa::find($request->recipientId);
        if (!$recipient) {
            return response()->json([
                'status' => 'error',
                'message' => 'Recipient not found'
            ], 404);
        }

        try {
            $feedback = Feedback::create([
                'mahasiswa_id' => $mahasiswa->id,
                'peer_id' => $request->recipientId,
                'group_id' => $group->id,
                'feedback' => $request->message,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Feedback submitted successfully',
                'data' => $feedback
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to submit feedback',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getUserGivenFeedbacks()
    {
        $mahasiswa = Mahasiswa::where('user_id', Auth::id())->first();

        if (!$mahasiswa) {
            return response()->json(['message' => 'Mahasiswa not found'], 404);
        }

        $feedbacks = DB::table('feedbacks')
            ->join('mahasiswa as peers', 'feedbacks.peer_id', '=', 'peers.id')
            ->join('users', 'peers.user_id', '=', 'users.id')
            ->select(
                'peers.id as peer_id',
                'users.name as peer_name',
                'feedbacks.feedback',
                'feedbacks.created_at'
            )
            ->where('feedbacks.mahasiswa_id', $mahasiswa->id)
            ->orderBy('feedbacks.created_at', 'desc')
            ->get();

        return response()->json($feedbacks);
    }
}
