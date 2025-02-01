<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Models\project;
use App\Models\Group;
use App\Models\User;
use App\Models\Assessment;
use App\Models\AnswersPeer;
use App\Models\Mahasiswa;
use App\Models\Dosen;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;


class AssessmentMahasiswa extends Controller
{
    public function selfAssessment()
    {
        return Inertia::render('Mahasiswa/ProjectSelfAssessment');
    }

    public function peerAssessment()
    {
        return Inertia::render('Mahasiswa/ProjectPeerAssessment');
    }

    public function getDataSelf()
    {
        $userId = Auth::id();
        $mahasiswa = Mahasiswa::where('user_id', $userId)->first();
        $groups = Group::where('mahasiswa_id', $mahasiswa->id)->get();
        $projectIds = $groups->pluck('project_id');
    
        $assessments = Assessment::whereIn('project_id', $projectIds)
            ->where('type', 'selfAssessment')
            ->with(['project'])
            ->get();
    
        if ($assessments->isEmpty()) {
            return response()->json(['error' => 'Tidak ada assessment untuk proyek ini'], 404);
        }
    
        return response()->json(['assessments' => $assessments]);
    }
    

    public function getDataPeer()
    {
        $userId = Auth::id();

        $projects = Project::whereExists(function ($query) {
            $query->from('assessment')
                ->whereColumn('project.batch_year', 'assessment.batch_year')
                ->whereColumn('project.project_name', 'assessment.project_name')
                ->where('assessment.type', 'peerAssessment');
        })
            ->whereExists(function ($query) use ($userId) {
                $query->from('kelompok')
                    ->whereColumn('project.batch_year', 'kelompok.batch_year')
                    ->whereColumn('project.project_name', 'kelompok.project_name')
                    ->where('kelompok.user_id', $userId);
            })

            ->get();

        return response()->json($projects);
    }
    public function getKelompokByUser(Request $request)
    {
        $userId = auth()->id();
        $tahunAjaran = $request->input('batch_year');
        $proyek = $request->input('proyek');

        if (!$tahunAjaran || !$proyek) {
            Log::warning('Tahun Ajaran atau Proyek tidak ditemukan pada request', [
                'batch_year' => $tahunAjaran,
                'proyek' => $proyek
            ]);
            return response()->json(['message' => 'Tahun ajaran atau proyek tidak ditemukan'], 400);
        }

        Log::info('Menerima request untuk getKelompokByUser', [
            'user_id' => $userId,
            'batch_year' => $tahunAjaran,
            'proyek' => $proyek
        ]);

        $totalQuestions = Assessment::where('batch_year', $tahunAjaran)
            ->where('project_name', $proyek)
            ->where('type', 'peerAssessment')
            ->count();

        $userGroup = Kelompok::where('user_id', $userId)
            ->where('batch_year', $tahunAjaran)
            ->where('project_name', $proyek)
            ->value('kelompok');

        $kelompok = Kelompok::with('user')
            ->where('kelompok', $userGroup)
            ->where('batch_year', $tahunAjaran)
            ->where('project_name', $proyek)
            ->where('user_id', '!=', $userId)
            ->get();

        $filteredKelompok = $kelompok->filter(function ($member) use ($userId, $totalQuestions) {
            $completedAnswers = AnswersPeer::where('user_id', $userId)
                ->where('peer_id', $member->user_id)
                ->count();

            return $completedAnswers < $totalQuestions;
        });

        Log::info('Query untuk getKelompokByUser', [
            'total_questions' => $totalQuestions,
            'original_members' => $kelompok->count(),
            'filtered_members' => $filteredKelompok->count(),
        ]);

        if ($filteredKelompok->isEmpty()) {
            Log::info('Tidak ada anggota kelompok yang tersisa untuk dinilai', [
                'user_id' => $userId,
                'batch_year' => $tahunAjaran,
                'proyek' => $proyek
            ]);
        }

        return response()->json($filteredKelompok->values());
    }

    public function searchByNim(Request $request)
    {
        $nim = $request->query('nim');
        $user = User::where('nim', $nim)->first();

        if ($user) {
            return response()->json(['user_id' => $user->id]);
        } else {
            return response()->json(['error' => 'User not found'], 404);
        }
    }
}
