<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Project;
use App\Models\Group;
use App\Models\User;
use App\Models\Mahasiswa;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\KelompokImport;
use App\Exports\KelompokExport;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;


class KelolaKelompokController extends Controller
{
    public function KelolaKelompok()
    {
        $allGroups = Group::select('group', 'project_id', 'dosen_id')
            ->with(['project', 'dosen.user'])
            ->get();

        Log::info(
            'All Unique Groups',
            $allGroups->map(function ($group) {
                return [
                    'group' => $group->group,
                    'project_name' => optional($group->project)->project_name,
                    'dosen_name' => optional($group->dosen->user)->name
                ];
            })->toArray()
        );

        $group2Details = Group::where('group', '2')
            ->with(['mahasiswa.user', 'dosen.user', 'project'])
            ->get();

        Log::info(
            'Group 2 Details',
            $group2Details->map(function ($group) {
                return [
                    'id' => $group->id,
                    'mahasiswa_name' => optional($group->mahasiswa->user)->name,
                    'dosen_name' => optional($group->dosen->user)->name,
                    'project_name' => optional($group->project)->project_name
                ];
            })->toArray()
        );

        $kelompokData = Group::with([
            'mahasiswa.user',
            'mahasiswa.classRoom',
            'dosen.user',
            'project'
        ])
        ->whereHas('project', function ($query) {
            $query->where('status', 'active'); 
        })
            ->get()
            ->groupBy('project_id')
            ->map(function ($projectGroups) {
                return $projectGroups->groupBy('group')
                    ->sortKeys()
                    ->map(function ($sameGroupItems) {
                        $firstGroup = $sameGroupItems->first();
                        $members = $sameGroupItems->map(function ($group) {
                            return [
                                'name' => optional($group->mahasiswa->user)->name ?? 'Unnamed',
                                'nim' => optional($group->mahasiswa)->nim ?? 'N/A',
                                'user_id' => optional($group->mahasiswa->user)->id ?? null
                            ];
                        })->unique('nim')->values();

                        // Ambil angkatan dari classroom mahasiswa di group
                        $angkatan = optional($firstGroup->mahasiswa->classRoom)->angkatan ?? 'N/A';

                        return [
                            'dosen_name' => optional($firstGroup->dosen->user)->name ?? 'Unnamed Dosen',
                            'projects' => [[
                                'project_name' => optional($firstGroup->project)->project_name ?? 'N/A',
                                'batch_year' => optional($firstGroup->project)->batch_year ?? 'N/A',
                                'group' => $firstGroup->group,
                                'anggota' => $members,
                                'angkatan' => $angkatan,
                                'classroom' => [
                                    'angkatan' => $angkatan
                                ]
                            ]]
                        ];
                    })
                    ->values();
            })
            ->flatten(1)
            ->filter()
            ->values();

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

    public function getProfile($user_id)
    {
        // Ambil data mahasiswa berdasarkan user_id yang diterima dari parameter
        $mahasiswa = Mahasiswa::with([
            'user',          // Relasi dengan tabel user
            'classRoom.prodi.major', // Relasi dengan class room dan prodi serta major
        ])
            ->where('user_id', $user_id) // Mengambil data mahasiswa berdasarkan user_id yang diterima
            ->first(); // Ambil hanya satu data mahasiswa (karena user hanya punya satu mahasiswa)

        if (!$mahasiswa) {
            return response()->json(['message' => 'Data mahasiswa tidak ditemukan.'], 404);
        }

        // Periksa apakah mahasiswa memiliki foto dan buat URL dengan asset()
        $photoUrl = $mahasiswa->user->photo ? asset('storage/' . $mahasiswa->user->photo) : null;

        // Kembalikan data mahasiswa dengan relasi terkait
        return response()->json([
            'nama' => $mahasiswa->user->name,
            'nim' => $mahasiswa->nim,
            'prodi' => $mahasiswa->classRoom->prodi->prodi_name,
            'jurusan' => $mahasiswa->classRoom->prodi->major->major_name,
            'email' => $mahasiswa->user->email,
            'telepon' => $mahasiswa->user->phone, // Misalkan ada kolom telepon di tabel user
            // 'photo' => $mahasiswa->user->photo, // Misalkan ada kolom photo di tabel user
            'photo' => $photoUrl,
        ]);
    }


    // Mendapatkan foto profil mahasiswa
    public function getProfilePhoto()
    {
        $user = Auth::user();
        $mahasiswa = Mahasiswa::where('user_id', $user->id)->first();

        if (!$mahasiswa || !$mahasiswa->user->photo) {
            return response()->json(['message' => 'Foto profil tidak ditemukan.'], 404);
        }

        // Mendapatkan URL untuk file foto profil
        $photoUrl = Storage::url($mahasiswa->user->photo);

        return response()->json(['photo_url' => $photoUrl]);
    }

    public function showDetail($id)
    {
        $kelompok = Group::with('user', 'dosen')->findOrFail($id);
        return Inertia::render('Dosen/DetailKelompok', [
            'kelompok' => $kelompok
        ]);
    }

    public function getAngkatan()
    {
        try {
            $angkatan = DB::table('class_room')
                ->distinct()
                ->orderBy('angkatan', 'desc')
                ->pluck('angkatan');

            return response()->json($angkatan);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Gagal mengambil data angkatan'], 500);
        }
    }

    public function exportTemplate(Request $request)
    {
        $request->validate([
            'batch_year' => 'required',
            'project_name' => 'required',
            'semester' => 'required',
            'angkatan' => 'required'
        ]);

        $batchYear = $request->input('batch_year');
        $projectName = $request->input('project_name');
        $semester = $request->input('semester');
        $angkatan = $request->input('angkatan');

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
                $semester,
                $angkatan
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
