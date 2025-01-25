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

    public function profile()
    {
        return Inertia::render('Mahasiswa/Profile');
    }

    public function getUserProject() {

        $user = Auth::user();
        $kelompok = Kelompok::where('user_id', $user->id)->first();
        if (!$kelompok) {
            return response()->json(['projects' => []]);
        }
        $projects = Project::where('tahun_ajaran', $kelompok->tahun_ajaran)
            ->where('status', 'aktif')
            ->get();
        return response()->json(['projects' => $projects]);
    }

    public function getSelfAssessmentStatus() {
        $user = Auth::user();
        
        $kelompok = Kelompok::where('user_id', $user->id)->first();
    
        if (!$kelompok) {
            return response()->json([
                'selfAssessmentStatus' => 'Not Started',
                'peerAssessmentStatus' => 'Not Started'
            ]);
        }
    
        $project = Project::where([
            'tahun_ajaran' => $kelompok->tahun_ajaran, 
            'nama_proyek' => $kelompok->nama_proyek
        ])->first();
    
        if (!$project) {
            return response()->json([
                'selfAssessmentStatus' => 'Not Started',
                'peerAssessmentStatus' => 'Not Started'
            ]);
        }
    
        $selfAssessmentQuestions = Assessment::where('type', 'selfAssessment')->count();
        $selfAssessmentCount = Answers::where('user_id', $user->id)->count();
    
        $groupPeers = Kelompok::where([
            'tahun_ajaran' => $kelompok->tahun_ajaran,
            'nama_proyek' => $kelompok->nama_proyek,
            'kelompok' => $kelompok->kelompok 
        ])->where('user_id', '!=', $user->id)->pluck('user_id');
    
        $peerAssessmentQuestions = Assessment::where('type', 'peerAssessment')->count();
        $totalExpectedPeerAnswers = $peerAssessmentQuestions * count($groupPeers);
    
        $peerAssessmentCount = AnswersPeer::where('user_id', $user->id)
            ->count();
    
        $selfAssessmentStatus = $selfAssessmentCount == 0 
            ? 'Not Started' 
            : ($selfAssessmentCount < $selfAssessmentQuestions ? 'Pending' : 'Completed');
    
        $peerAssessmentStatus = $peerAssessmentCount == 0 
            ? 'Not Started' 
            : ($peerAssessmentCount < $totalExpectedPeerAnswers ? 'Pending' : 'Completed');
    
        return response()->json([
            'selfAssessmentStatus' => $selfAssessmentStatus,
            'peerAssessmentStatus' => $peerAssessmentStatus,
            'totalQuestions' => $selfAssessmentQuestions + $peerAssessmentQuestions,
            'selfAssessmentCount' => $selfAssessmentCount,
            'peerAssessmentCount' => $peerAssessmentCount,
            'selfAssessmentQuestions' => $selfAssessmentQuestions,
            'totalExpectedPeerAnswers' => $totalExpectedPeerAnswers,
            'groupPeersCount' => count($groupPeers)
        ]);
    }

    public function getPeerAssessmentDetails() {
        $user = Auth::user();
        $kelompok = Kelompok::where('user_id', $user->id)->first();
        
        if (!$kelompok) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kelompok not found'
            ]);
        }
        
        $groupPeers = Kelompok::where([
            'tahun_ajaran' => $kelompok->tahun_ajaran,
            'nama_proyek' => $kelompok->nama_proyek,
            'kelompok' => $kelompok->kelompok
        ])->where('user_id', '!=', $user->id)->get();
        
        $groupPeerIds = $groupPeers->pluck('user_id');
        $peerAssessmentQuestions = Assessment::where('type', 'peerAssessment')->count();
        
        $peerCompletedCount = 0;
        
        $completedPeerAssessments = $groupPeerIds->mapWithKeys(function($peerId) use ($user, $peerAssessmentQuestions, &$peerCompletedCount) {
            $completedCount = AnswersPeer::where('user_id', $user->id)
                ->where('peer_id', $peerId)
                ->count();
            
            $isCompleted = $completedCount == $peerAssessmentQuestions;
            
            if ($isCompleted) {
                $peerCompletedCount++;
            }
            
            return [$peerId => [
                'total_completed' => $completedCount,
                'is_completed' => $isCompleted
            ]];
        });
        
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
