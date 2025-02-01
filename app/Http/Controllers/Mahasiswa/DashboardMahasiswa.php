<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Kelompok;
use App\Models\Project;
use App\Models\Answers;
use App\Models\AnswersPeer;
use App\Models\Assessment;
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
        $kelompok = Kelompok::where('user_id', $user->id)->get();
        
        if ($kelompok->isEmpty()) {
            return response()->json(['projects' => []]);
        }
        
        $projects = Project::whereIn('tahun_ajaran', $kelompok->pluck('tahun_ajaran'))
            ->where('status', 'aktif')
            ->get();
        
        return response()->json(['projects' => $projects]);
    }

    public function getSelfAssessmentStatus() {
        $user = Auth::user();
        
        $userProjects = Kelompok::where('user_id', $user->id)
            ->select('tahun_ajaran', 'nama_proyek', 'kelompok')
            ->distinct()
            ->get();
        
        if ($userProjects->isEmpty()) {
            return response()->json([
                'selfAssessmentStatus' => 'Not Started',
                'peerAssessmentStatus' => 'Not Started',
                'projects' => []
            ]);
        }
        
        $selfAssessmentQuestions = Assessment::where('type', 'selfAssessment')->count();
        $peerAssessmentQuestions = Assessment::where('type', 'peerAssessment')->count();
        
        $projectStatuses = $userProjects->map(function($kelompok) use ($user, $selfAssessmentQuestions, $peerAssessmentQuestions) {
            $groupPeers = Kelompok::where([
                'tahun_ajaran' => $kelompok->tahun_ajaran,
                'nama_proyek' => $kelompok->nama_proyek,
                'kelompok' => $kelompok->kelompok 
            ])->where('user_id', '!=', $user->id)->pluck('user_id');
        
            $selfAssessmentCount = Answers::whereHas('question', function($query) use ($kelompok) {
                    $query->where('type', 'selfAssessment')
                          ->where('tahun_ajaran', $kelompok->tahun_ajaran)
                          ->where('nama_proyek', $kelompok->nama_proyek);
                })
                ->where('user_id', $user->id)
                ->count();
        
            $totalExpectedPeerAnswers = $peerAssessmentQuestions * count($groupPeers);
        
            $peerAssessmentCount = AnswersPeer::where('user_id', $user->id)
                ->whereHas('kelompok', function($query) use ($kelompok) {
                    $query->where('tahun_ajaran', $kelompok->tahun_ajaran)
                          ->where('nama_proyek', $kelompok->nama_proyek);
                })
                ->count();
        
            $selfAssessmentStatus = $selfAssessmentCount == 0 
                ? 'Not Started' 
                : ($selfAssessmentCount < $selfAssessmentQuestions ? 'Pending' : 'Completed');
        
            $peerAssessmentStatus = $peerAssessmentCount == 0 
                ? 'Not Started' 
                : ($peerAssessmentCount < $totalExpectedPeerAnswers ? 'Pending' : 'Completed');
        
            return [
                'tahun_ajaran' => $kelompok->tahun_ajaran,
                'nama_proyek' => $kelompok->nama_proyek,
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
        
        $selectedProject = $request->input('project');
        
        $query = Kelompok::where('user_id', $user->id);
        if ($selectedProject) {
            $query->where('nama_proyek', $selectedProject);
        }
        
        $kelompok = $query->first();
        
        if (!$kelompok) {
            return response()->json([
                'status' => 'error',
                'message' => 'No projects found'
            ], 404);
        }
        
        $groupPeers = Kelompok::where([
            'tahun_ajaran' => $kelompok->tahun_ajaran,
            'nama_proyek' => $kelompok->nama_proyek,
            'kelompok' => $kelompok->kelompok
        ])->where('user_id', '!=', $user->id)->get();
        
        $groupPeerIds = $groupPeers->pluck('user_id');
        $peerAssessmentQuestions = Assessment::where('type', 'peerAssessment')->count();
        
        $completedPeerAssessments = $groupPeerIds->mapWithKeys(function($peerId) use ($user, $kelompok, $peerAssessmentQuestions) {
            $completedCount = AnswersPeer::where('user_id', $user->id)
                ->where('peer_id', $peerId)
                ->whereHas('kelompok', function($query) use ($kelompok) {
                    $query->where('tahun_ajaran', $kelompok->tahun_ajaran)
                          ->where('nama_proyek', $kelompok->nama_proyek);
                })
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
                    'id' => $peer->user_id,
                    'name' => $peer->user->name
                ];
            }),
            'completed_peer_assessments' => $completedPeerAssessments,
            'peer_completed_count' => $peerCompletedCount
        ]);
    }
}
