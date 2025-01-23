<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Models\project;
use App\Models\Kelompok;
use App\Models\User;
use App\Models\Assessment;
use App\Models\AnswersPeer;
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
        $userId = Auth::id(); // ID user yang sedang login

        // Ambil proyek hanya jika user terdaftar di kelompok proyek tersebut
        $projects = Project::whereExists(function ($query) {
            $query->from('assessment')
                ->whereColumn('project.tahun_ajaran', 'assessment.tahun_ajaran')
                ->whereColumn('project.nama_proyek', 'assessment.nama_proyek')
                ->where('assessment.type', 'selfAssessment');
        })
            ->whereExists(function ($query) use ($userId) {
                $query->from('kelompok')
                    ->whereColumn('project.tahun_ajaran', 'kelompok.tahun_ajaran')
                    ->whereColumn('project.nama_proyek', 'kelompok.nama_proyek')
                    ->where('kelompok.user_id', $userId); // Cek user_id di tabel kelompok
            })

            ->get();

        return response()->json($projects);
    }

    public function getDataPeer()
    {
        $userId = Auth::id(); // ID user yang sedang login

        // Ambil proyek hanya jika user terdaftar di kelompok proyek tersebut
        $projects = Project::whereExists(function ($query) {
            $query->from('assessment')
                ->whereColumn('project.tahun_ajaran', 'assessment.tahun_ajaran')
                ->whereColumn('project.nama_proyek', 'assessment.nama_proyek')
                ->where('assessment.type', 'peerAssessment');
        })
            ->whereExists(function ($query) use ($userId) {
                $query->from('kelompok')
                    ->whereColumn('project.tahun_ajaran', 'kelompok.tahun_ajaran')
                    ->whereColumn('project.nama_proyek', 'kelompok.nama_proyek')
                    ->where('kelompok.user_id', $userId); // Cek user_id di tabel kelompok
            })

            ->get();

        return response()->json($projects);
    }
    public function getKelompokByUser(Request $request)
    {
        $userId = auth()->id();
        $tahunAjaran = $request->input('tahun_ajaran');
        $proyek = $request->input('proyek');

        if (!$tahunAjaran || !$proyek) {
            Log::warning('Tahun Ajaran atau Proyek tidak ditemukan pada request', [
                'tahun_ajaran' => $tahunAjaran,
                'proyek' => $proyek
            ]);
            return response()->json(['message' => 'Tahun ajaran atau proyek tidak ditemukan'], 400);
        }

        Log::info('Menerima request untuk getKelompokByUser', [
            'user_id' => $userId,
            'tahun_ajaran' => $tahunAjaran,
            'proyek' => $proyek
        ]);

        // Get total number of peer assessment questions
        $totalQuestions = Assessment::where('tahun_ajaran', $tahunAjaran)
            ->where('nama_proyek', $proyek)
            ->where('type', 'peerAssessment')
            ->count();

        // Get the user's group
        $userGroup = Kelompok::where('user_id', $userId)
            ->where('tahun_ajaran', $tahunAjaran)
            ->where('nama_proyek', $proyek)
            ->value('kelompok');

        // Query group members
        $kelompok = Kelompok::with('user')
            ->where('kelompok', $userGroup)
            ->where('tahun_ajaran', $tahunAjaran)
            ->where('nama_proyek', $proyek)
            ->where('user_id', '!=', $userId) // Exclude current user
            ->get();

        // Filter out members who have completed all peer assessments
        $filteredKelompok = $kelompok->filter(function ($member) use ($userId, $totalQuestions) {
            // Count completed answers for this peer
            $completedAnswers = AnswersPeer::where('user_id', $userId)
                ->where('peer_id', $member->user_id)
                ->count();

            // Keep member only if they haven't been fully assessed
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
                'tahun_ajaran' => $tahunAjaran,
                'proyek' => $proyek
            ]);
        }

        return response()->json($filteredKelompok->values());
    }

    public function searchByNim(Request $request)
    {
        $nim = $request->query('nim');
        $user = User::where('nim', $nim)->first(); // Cari berdasarkan nim

        if ($user) {
            return response()->json(['user_id' => $user->id]);
        } else {
            return response()->json(['error' => 'User not found'], 404);
        }
    }
}
