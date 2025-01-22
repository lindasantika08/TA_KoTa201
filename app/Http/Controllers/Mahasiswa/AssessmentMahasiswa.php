<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Models\project;
use App\Models\Kelompok;
use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;


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
        $projects = Project::whereExists(function ($query) {
            $query->from('assessment')
                ->whereColumn('project.tahun_ajaran', 'assessment.tahun_ajaran')
                ->whereColumn('project.nama_proyek', 'assessment.nama_proyek')
                ->where('assessment.type', 'selfAssessment');
        })->get();

        return response()->json($projects);
    }

    public function getDataPeer()
    {
        $projects = Project::whereExists(function ($query) {
            $query->from('assessment')
                ->whereColumn('project.tahun_ajaran', 'assessment.tahun_ajaran')
                ->whereColumn('project.nama_proyek', 'assessment.nama_proyek')
                ->where('assessment.type', 'peerAssessment');
        })->get();

        return response()->json($projects);
    }

    public function getKelompokByUser(Request $request)
    {
        $userId = auth()->id(); // Mengambil ID user yang sedang login

        // Query data kelompok berdasarkan user
        $kelompok = Kelompok::with('user') // Pastikan relasi user di-load
            ->where('kelompok', function ($query) use ($userId) {
                // Ambil kelompok user yang sedang login
                $query->select('kelompok')
                    ->from('kelompok')
                    ->where('user_id', $userId)
                    ->limit(1);
            })
            ->get();

        return response()->json($kelompok);
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
