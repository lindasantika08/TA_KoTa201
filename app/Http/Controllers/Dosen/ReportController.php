<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use App\Models\Kelompok;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Project;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    public function report()
    {
        return Inertia::render('Dosen/Report');
    }

    public function getDropdownOptions(Request $request): JsonResponse
    {
        // Ambil data tahun ajaran yang unik
        $tahunAjaranOptions = Project::select('tahun_ajaran')->distinct()->pluck('tahun_ajaran');

        // Jika ada tahun ajaran yang dipilih, ambil nama proyek berdasarkan tahun ajaran tersebut
        $namaProyekOptions = Project::select('nama_proyek', 'tahun_ajaran')
            ->when($request->tahun_ajaran, function ($query, $tahunAjaran) {
                return $query->where('tahun_ajaran', $tahunAjaran);
            })
            ->distinct()
            ->get();

        // Gabungkan data tahun ajaran dan nama proyek
        $combinedOptions = [];
        foreach ($tahunAjaranOptions as $tahun) {
            foreach ($namaProyekOptions->where('tahun_ajaran', $tahun) as $proyek) {
                $combinedOptions[] = [
                    'value' => "{$tahun} - {$proyek->nama_proyek}",
                    'label' => "{$tahun} - {$proyek->nama_proyek}", // Atau bisa menggunakan 'value' jika hanya ingin memilih tahun ajaran dan nama proyek
                    'tahunAjaran' => $tahun,
                    'namaProyek' => $proyek->nama_proyek,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'options' => $combinedOptions,
        ]);
    }

    public function getKelompokReport(Request $request): JsonResponse
    {
        // Validasi parameter yang diterima
        $request->validate([
            'tahun_ajaran' => 'required|string',
            'nama_proyek' => 'required|string',
        ]);

        // Ambil data kelompok berdasarkan tahun ajaran dan nama proyek
        $kelompokData = Kelompok::where('tahun_ajaran', $request->tahun_ajaran)
            ->where('nama_proyek', $request->nama_proyek)
            ->with('user') // Eager load relasi user
            ->get(['id', 'kelompok', 'user_id']); // Ambil kolom id, kelompok, dan user_id

        // Jika tidak ada data, beri respon kosong
        if ($kelompokData->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada kelompok yang ditemukan.',
            ]);
        }

        // Group data berdasarkan nama kelompok (kelompok)
        $groupedKelompok = $kelompokData->groupBy('kelompok');

        // Format data kelompok dan merge jika ada kelompok yang sama
        $kelompokList = $groupedKelompok->map(function ($group, $kelompokName) {
            // Ambil ID pertama dari kelompok
            $kelompok = [
                'id' => $group->pluck('id')->first(),  // ID kelompok
                'nama_kelompok' => $kelompokName,  // Nama kelompok
                'anggota' => [], // Menyimpan anggota kelompok
            ];

            // Ambil semua anggota dengan user_id terkait
            foreach ($group as $item) {
                // Menambahkan nama anggota berdasarkan user_id
                $kelompok['anggota'][] = [
                    'user_id' => $item->user_id,
                    'name' => $item->user->name,  // Ambil nama pengguna dari relasi user
                ];
            }

            return $kelompok;
        });

        return response()->json([
            'success' => true,
            'kelompok' => $kelompokList,
        ]);
    }

    public function getScoreKelompok(Request $request)
    {
        // Ambil parameter dari query string
        $tahunAjaran = $request->query('tahun_ajaran');
        $namaProyek = $request->query('nama_proyek');
        $kelompok = $request->query('kelompok');

        // Log data untuk melihat apakah parameter diterima
        Log::info('Data diterima di controller:', [
            'tahun_ajaran' => $tahunAjaran,
            'nama_proyek' => $namaProyek,
            'kelompok' => $kelompok,
        ]);

        // Cek apakah parameter ada dan ambil data kelompok
        $kelompokDetail = Kelompok::where('tahun_ajaran', $tahunAjaran)
            ->where('nama_proyek', $namaProyek)
            ->where('kelompok', $kelompok)
            ->first();

        return Inertia::render('Dosen/ReportScore', [
            'tahunAjaran' => $tahunAjaran,
            'namaProyek' => $namaProyek,
            'kelompok' => $kelompok,
        ]);
    }
}
