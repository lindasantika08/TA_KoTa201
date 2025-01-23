<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Project;  // Model untuk mengambil data proyek
use App\Models\Kelompok; // Model untuk tabel kelompok
use App\Models\GroupMember;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\KelompokImport; // Untuk import data Excel
use App\Exports\KelompokExport; // Untuk export template
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Log;


class KelolaKelompokController extends Controller
{
    public function KelolaKelompok()
    {
        $kelompokData = Kelompok::with('user', 'dosen')->get();
        Log::info('Data Kelompok yang Dikirim:', ['kelompok' => $kelompokData]);


        // Transformasi data
        $kelompok = $kelompokData
            ->groupBy(function ($item) {
                return $item->tahun_ajaran . '-' . $item->kelompok;
            }) // Mengelompokkan data berdasarkan kombinasi tahun ajaran dan nama kelompok
            ->map(function ($items) {
                $first = $items->first();
                return [
                    'id' => $first->id,
                    'tahun_ajaran' => $first->tahun_ajaran,
                    'nama_proyek' => $first->nama_proyek,
                    'kelompok' => $first->kelompok,
                    'dosen' => $first->dosen->name ?? '-', // Nama dosen
                    'anggota' => $items->pluck('user.name')->unique()->toArray(), // Nama anggota unik
                ];
            })
            ->sortBy('kelompok') // Urutkan kelompok dari kecil ke besar
            ->values(); // Reset index agar menjadi array numerik

        return Inertia::render('Dosen/KelolaKelompok', [
            'kelompok' => $kelompok,
        ]);
    }
    public function CreateKelompok()
    {
        return Inertia::render('Dosen/CreateKelompok');
    }

    public function ProfileMhs()
    {
        return Inertia::render('Dosen/DetailProfilMhs');
    }

    public function showDetail($id)
    {
        $kelompok = Kelompok::with('user', 'dosen')->findOrFail($id);
        return Inertia::render('Dosen/DetailKelompok', [
            'kelompok' => $kelompok
        ]);
    }

    public function exportTemplate(Request $request)
    {
        // Mengambil tahun ajaran dan proyek dari parameter
        $tahunAjaran = $request->input('tahun_ajaran');
        $namaProyek = $request->input('nama_proyek');

        if (!$tahunAjaran || !$namaProyek) {
            return response()->json(['error' => 'Parameter tidak lengkap.'], 400);
        }

        // Kirim data ke export untuk menghasilkan template
        return Excel::download(new KelompokExport($tahunAjaran, $namaProyek), 'Data_Kelompok.xlsx');
    }

    public function importData(Request $request)
    {
        // Validasi input request untuk memastikan file Excel valid
        $request->validate([
            'file' => 'required|mimes:xlsx,xls',
        ]);

        // Mendapatkan file yang diupload
        $file = $request->file('file');

        try {
            // Log informasi tentang file yang diupload
            Log::info('File uploaded', ['file_name' => $file->getClientOriginalName()]);

            // Menggunakan Maatwebsite\Excel untuk mengimpor data
            Excel::import(new KelompokImport, $file);

            // Jika berhasil mengimpor data
            return response()->json(['message' => 'Data kelompok berhasil diimpor'], 200);
        } catch (\Exception $e) {
            // Jika ada error, tangkap dan tampilkan pesan error
            Log::error('Import error: ' . $e->getMessage());
            return response()->json(['error' => 'Terjadi kesalahan saat mengimpor data', 'details' => $e->getMessage()], 500);
        }
    }
}
