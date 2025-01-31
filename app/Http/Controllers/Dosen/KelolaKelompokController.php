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
        $kelompokData = Group::with(['mahasiswa.user', 'dosen.user', 'project'])
            ->get()
            ->groupBy(['project_id', 'group'])
            ->map(function ($projectGroups) {
                $firstGroup = $projectGroups->first()->first();
                $allMembers = $projectGroups->flatten()->map(function ($item) {
                    return [
                        'name' => $item->mahasiswa->user->name,
                        'user_id' => $item->mahasiswa->user->id
                    ];
                })->unique('user_id')->values();

                return [
                    'id' => $firstGroup->id,
                    'batch_year' => $firstGroup->project->batch_year,
                    'project_name' => $firstGroup->project->project_name,
                    'group' => $firstGroup->group,
                    'dosen' => $firstGroup->dosen ? $firstGroup->dosen->user->name : '-',
                    'anggota' => $allMembers,
                ];
            })
            ->values();

        Log::info('Data Kelompok yang Dikirim:', ['kelompok' => $kelompokData]);

        return Inertia::render('Dosen/KelolaKelompok', [
            'kelompok' => $kelompokData,
        ]);
    }

    public function CreateKelompok()
    {
        return Inertia::render('Dosen/CreateKelompok');
    }

    public function ProfileMhs(Request $request)
    {
        $userId = $request->input('user_id');
        $user = User::find($userId);
        
        if (!$user) {
            Log::warning('User not found:', ['user_id' => $userId]);
            return redirect()->back()->with('error', 'User tidak ditemukan');
        }
        
        Log::info('User found:', ['user_id' => $userId, 'name' => $user->name]);

        return Inertia::render('Dosen/DetailProfilMhs', [
            'user_id' => $userId,
            'user_name' => $user->name,
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
        $request->validate([
            'file' => 'required|mimes:xlsx,xls',
        ]);

        $file = $request->file('file');

        try {
            Log::info('File uploaded', ['file_name' => $file->getClientOriginalName()]);

            Excel::import(new KelompokImport, $file);

            return response()->json(['message' => 'Data kelompok berhasil diimpor'], 200);
        } catch (\Exception $e) {
            Log::error('Import error: ' . $e->getMessage());
            return response()->json(['error' => 'Terjadi kesalahan saat mengimpor data', 'details' => $e->getMessage()], 500);
        }
    }
}
