<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Project; 
use App\Models\Group;
use App\Models\User;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\KelompokImport; 
use App\Exports\KelompokExport;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Log;


class KelolaKelompokController extends Controller
{
    public function KelolaKelompok()
    {
        $kelompokData = Group::with('user', 'dosen')->get();
        Log::info('Data Kelompok yang Dikirim:', ['kelompok' => $kelompokData]);


        $kelompok = $kelompokData
            ->groupBy(function ($item) {
                return $item->tahun_ajaran . '-' . $item->kelompok;
            })
            ->map(function ($items) {
                $first = $items->first();
                return [
                    'id' => $first->id,
                    'tahun_ajaran' => $first->tahun_ajaran,
                    'nama_proyek' => $first->nama_proyek,
                    'kelompok' => $first->kelompok,
                    'dosen' => $first->dosen->name ?? '-',
                    'anggota' => $items->map(function ($item) {
                    return [
                        'name' => $item->user->name,
                        'user_id' => $item->user->id 
                    ];
                })->unique('user_id')->toArray(), 
                ];
            })
            ->sortBy('kelompok')
            ->values();

        return Inertia::render('Dosen/KelolaKelompok', [
            'kelompok' => $kelompok,
        ]);
    }
    public function CreateKelompok()
    {
        return Inertia::render('Dosen/CreateKelompok');
    }

    public function ProfileMhs(Request $request)
    {
        $userId = $request->input('user_id');
        $user = User::find($userId); // Ambil user berdasarkan user_id
        Log::info('user id:', ['user_id' => $userId]);

        return Inertia::render('Dosen/DetailProfilMhs', [
            'user_id' => $userId,
            'user_name' => $user ? $user->name : 'User Not Found', // Kirimkan nama user
        ]);
    }

    public function showDetail($id)
    {
        $kelompok = Group::with('user', 'dosen')->findOrFail($id);
        return Inertia::render('Dosen/DetailKelompok', [
            'kelompok' => $kelompok
        ]);
    }

    public function exportTemplate(Request $request)
    {
        $request->validate([
            'batch_year' => 'required',
            'project_name' => 'required',
            'semester' => 'required'
        ]);

        $batchYear = $request->input('batch_year');
        $projectName = $request->input('project_name');
        $semester = $request->input('semester');

        $project = Project::where('batch_year', $batchYear)
            ->where('project_name', $projectName)
            ->where('semester', $semester)
            ->first();

        if (!$project) {
            return response()->json(['error' => 'Project tidak ditemukan.'], 404);
        }

        return Excel::download(
            new KelompokExport(
                $batchYear,
                $projectName,
                $semester
            ), 
            'Data_Kelompok.xlsx'
        );
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
