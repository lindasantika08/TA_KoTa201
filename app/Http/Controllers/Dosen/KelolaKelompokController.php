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
        return Inertia::render('Dosen/KelolaKelompok');
    }

    public function ViewKelompok()
    {
        return Inertia::render('Dosen/ViewKelompok');
    }

    public function getKelompokData()
    {
        // Mengambil semua data kelompok
        $kelompokData = Kelompok::all();

        // Mengembalikan data dalam format JSON
        return response()->json($kelompokData);
    }

    // public function KelolaKelompok()
    // {
    //     // Ambil data proyek untuk dropdown
    //     $projects = Project::all();
    //     return view('dosen.kelola-kelompok', compact('projects'));
    // }

    public function exportTemplate(Request $request)
    {
        // Mengambil tahun ajaran dan proyek dari parameter
        $tahunAjaran = $request->input('tahun_ajaran');
        $namaProyek = $request->input('nama_proyek');

        // Kirim data ke export untuk menghasilkan template
        return Excel::download(new KelompokExport($tahunAjaran, $namaProyek), 'template_kelompok.xlsx');
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
