<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Group;
use App\Models\Project;
use App\Models\Answers;
use App\Models\AnswersPeer;
use App\Models\Assessment;
use App\Models\Mahasiswa;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;


class DashboardMahasiswa extends Controller
{
    public function dashboard()
    {
        return Inertia::render('Mahasiswa/Dashboard');
    }

    public function getMahasiswaByClass($classId) {
        $mahasiswaKelas = User::whereHas('mahasiswa', function($query) use ($classId) {
            $query->where('class_id', $classId);
        })->get();

        return response()->json([
            'data' => $mahasiswaKelas
        ]);
    }

    public function getUserProject() {
        $user = Auth::user();
        
        $mahasiswa = Mahasiswa::where('user_id', $user->id)->first();
        
        if (!$mahasiswa) {
            return response()->json(['projects' => []]);
        }
        
        $projects = $mahasiswa->group()->with('project')
            ->whereHas('project', function($query) {
                $query->where('status', 'Active');
            })
            ->get()
            ->pluck('project');
        
        return response()->json(['projects' => $projects]);
    }

    public function getSelfAssessmentStatus() {
        $user = Auth::user();
        
        $mahasiswa = Mahasiswa::where('user_id', $user->id)->first();
        
        if (!$mahasiswa) {
            return response()->json([
                'selfAssessmentStatus' => 'Not Started',
                'peerAssessmentStatus' => 'Not Started',
                'projects' => []
            ]);
        }
        
        $userGroups = Group::where('mahasiswa_id', $mahasiswa->id)
            ->with('project')
            ->get();
        
        if ($userGroups->isEmpty()) {
            return response()->json([
                'selfAssessmentStatus' => 'Not Started',
                'peerAssessmentStatus' => 'Not Started',
                'projects' => []
            ]);
        }
        
        $projectStatuses = $userGroups->map(function($group) use ($mahasiswa) {
            $project = $group->project;
            
            $selfAssessmentQuestions = Assessment::where('type', 'selfAssessment')
                ->where('project_id', $project->id)
                ->count();
            
            $peerAssessmentQuestions = Assessment::where('type', 'peerAssessment')
                ->where('project_id', $project->id)
                ->count();
            
            $groupPeers = Group::where('project_id', $project->id)
                ->where('group', $group->group)
                ->where('mahasiswa_id', '!=', $mahasiswa->id)
                ->pluck('mahasiswa_id');
            
            $selfAssessmentCount = Answers::whereHas('question', function($query) use ($project) {
                    $query->where('type', 'selfAssessment')
                          ->where('project_id', $project->id);
                })
                ->where('mahasiswa_id', $mahasiswa->id)
                ->count();
            
            $totalExpectedPeerAnswers = $peerAssessmentQuestions * count($groupPeers);
            
            $peerAssessmentCount = Answers::where('mahasiswa_id', $mahasiswa->id)
                ->whereHas('question', function($query) use ($project) {
                    $query->where('type', 'peerAssessment')
                          ->where('project_id', $project->id);
                })
                ->count();
            
            $selfAssessmentStatus = $selfAssessmentCount == 0 
                ? 'Not Started' 
                : ($selfAssessmentCount < $selfAssessmentQuestions ? 'Pending' : 'Completed');
            
            $peerAssessmentStatus = $peerAssessmentCount == 0 
                ? 'Not Started' 
                : ($peerAssessmentCount < $totalExpectedPeerAnswers ? 'Pending' : 'Completed');
            
            return [
                'batch_year' => $project->batch_year,
                'project_name' => $project->project_name,
                'selfAssessmentStatus' => $selfAssessmentStatus,
                'peerAssessmentStatus' => $peerAssessmentStatus,
                'selfAssessmentCount' => $selfAssessmentCount,
                'peerAssessmentCount' => $peerAssessmentCount,
                'selfAssessmentQuestions' => $selfAssessmentQuestions,
                'totalExpectedPeerAnswers' => $totalExpectedPeerAnswers,
                'groupPeersCount' => count($groupPeers)
            ];
        });
        
        $overallSelfAssessmentStatus = $projectStatuses->every(function($status) {
            return $status['selfAssessmentStatus'] === 'Completed';
        }) ? 'Completed' : 'Pending';
        
        $overallPeerAssessmentStatus = $projectStatuses->every(function($status) {
            return $status['peerAssessmentStatus'] === 'Completed';
        }) ? 'Completed' : 'Pending';
        
        return response()->json([
            'overallSelfAssessmentStatus' => $overallSelfAssessmentStatus,
            'overallPeerAssessmentStatus' => $overallPeerAssessmentStatus,
            'projects' => $projectStatuses
        ]);
    }

    public function getPeerAssessmentDetails(Request $request) {
        $user = Auth::user();
        
        $mahasiswa = Mahasiswa::where('user_id', $user->id)->first();
        
        if (!$mahasiswa) {
            return response()->json([
                'status' => 'error',
                'message' => 'Mahasiswa not found'
            ], 404);
        }
        
        $selectedProject = $request->input('project');
        
        $query = Group::where('mahasiswa_id', $mahasiswa->id)
            ->with('project');
        
        if ($selectedProject) {
            $query->whereHas('project', function($q) use ($selectedProject) {
                $q->where('project_name', $selectedProject);
            });
        }
        
        $group = $query->first();
        
        if (!$group) {
            return response()->json([
                'status' => 'error',
                'message' => 'No projects found'
            ], 404);
        }
        
        $project = $group->project;
        
        $groupPeers = Group::where('project_id', $project->id)
            ->where('group', $group->group)
            ->where('mahasiswa_id', '!=', $mahasiswa->id)
            ->with('mahasiswa')
            ->get();
        
        $groupPeerIds = $groupPeers->pluck('mahasiswa_id');
        
        $peerAssessmentQuestions = Assessment::where('type', 'peerAssessment')
            ->where('project_id', $project->id)
            ->count();
        
        $completedPeerAssessments = $groupPeerIds->mapWithKeys(function($peerId) use ($mahasiswa, $project, $peerAssessmentQuestions) {
            $completedCount = AnswersPeer::where('mahasiswa_id', $mahasiswa->id)
                ->whereHas('question', function($query) use ($project, $peerId) {
                    $query->where('type', 'peerAssessment')
                          ->where('project_id', $project->id);
                })
                ->where('peer_id', $peerId)
                ->count();
            
            $isCompleted = $completedCount == $peerAssessmentQuestions;
            
            return [$peerId => [
                'total_completed' => $completedCount,
                'is_completed' => $isCompleted
            ]];
        });
        
        $peerCompletedCount = $completedPeerAssessments->filter(function($peer) {
            return $peer['is_completed'];
        })->count();
        
        return response()->json([
            'group_size' => $groupPeers->count(),
            'group_peers' => $groupPeers->map(function($peer) {
                return [
                    'id' => $peer->mahasiswa_id,
                    'name' => $peer->mahasiswa->user->name
                ];
            }),
            'completed_peer_assessments' => $completedPeerAssessments,
            'peer_completed_count' => $peerCompletedCount
        ]);
    }
}
