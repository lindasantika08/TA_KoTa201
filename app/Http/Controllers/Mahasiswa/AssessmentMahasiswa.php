<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Models\project;
use App\Models\Kelompok;
use App\Models\User;
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
        $userId = auth()->id(); // Mengambil ID user yang sedang login

        // Mengambil parameter tahun_ajaran dan nama_proyek dari request
        $tahunAjaran = $request->input('tahun_ajaran');
        $proyek = $request->input('proyek');

        // Cek apakah tahun_ajaran atau proyek kosong
        if (!$tahunAjaran || !$proyek) {
            Log::warning('Tahun Ajaran atau Proyek tidak ditemukan pada request', [
                'tahun_ajaran' => $tahunAjaran,
                'proyek' => $proyek
            ]);
            return response()->json(['message' => 'Tahun ajaran atau proyek tidak ditemukan'], 400);
        }

        // Menambahkan log untuk melihat data yang diterima
        Log::info('Menerima request untuk getKelompokByUser', [
            'user_id' => $userId,
            'tahun_ajaran' => $tahunAjaran,
            'proyek' => $proyek
        ]);

        // Query data kelompok berdasarkan user dan filter tahun ajaran serta proyek
        $kelompok = Kelompok::with('user') // Pastikan relasi user di-load
            ->where('kelompok', function ($query) use ($userId) {
                // Ambil kelompok user yang sedang login
                $query->select('kelompok')
                    ->from('kelompok')
                    ->where('user_id', $userId)
                    ->limit(1);
            })
            ->where('tahun_ajaran', $tahunAjaran) // Menambahkan kondisi tahun_ajaran
            ->where('nama_proyek', $proyek) // Menambahkan kondisi nama_proyek
            ->get();

        // Menambahkan log untuk memeriksa query dan hasilnya
        Log::info('Query untuk getKelompokByUser', [
            'query' => $kelompok->toArray()
        ]);

        // Mengecek jika data kosong
        if ($kelompok->isEmpty()) {
            Log::warning('Tidak ada data kelompok yang ditemukan untuk user_id', [
                'user_id' => $userId,
                'tahun_ajaran' => $tahunAjaran,
                'proyek' => $proyek
            ]);
        }

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
