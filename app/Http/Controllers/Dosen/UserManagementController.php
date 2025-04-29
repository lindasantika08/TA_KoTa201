<?php

namespace App\Http\Controllers\Dosen;

use App\Models\User;
use App\Models\Mahasiswa;
use App\Models\Dosen;
use App\Models\Major;
use App\Models\Prodi;
use App\Models\ClassRoom;
use App\Models\Group;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\MahasiswaExport;
use App\Imports\MahasiswaImport;
use App\Exports\DosenExport;
use App\Imports\DosenImport;
use Inertia\Inertia;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\DB;



class UserManagementController extends Controller
{

    public function ManageMahasiswa()
    {

        return Inertia::render('Dosen/ManageMahasiswa');
    }

    public function DetailMahasiswa(Request $request)
    {
        $userId = $request->input('user_id');
        $user = User::find($userId);

        if (!$user) {
            Log::warning('User not found:', ['user_id' => $userId]);
            return redirect()->back()->with('error', 'User tidak ditemukan');
        }

        Log::info('User found:', ['user_id' => $userId, 'name' => $user->name]);

        return Inertia::render('Dosen/DetailMahasiswa', [
            'user_id' => $userId,
            'user_name' => $user->name,
        ]);
    }

    public function getMahasiswa(Request $request)
    {
        $angkatan = $request->get('angkatan');
        $class = $request->get('class_name');
        $prodi = $request->get('prodi');
        $major = $request->get('major');

        $query = Mahasiswa::with(['classRoom.prodi.major', 'user'])
            ->join('class_room', 'mahasiswa.class_id', '=', 'class_room.id')
            ->join('prodi', 'class_room.prodi_id', '=', 'prodi.id')
            ->join('major', 'prodi.major_id', '=', 'major.id')
            ->orderBy('class_room.class_name', 'asc')
            ->select('mahasiswa.*', 'major.major_name');

        // Search by name or NIM
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($subQ) use ($search) {
                    $subQ->where('name', 'like', "%{$search}%");
                })->orWhere('mahasiswa.nim', 'like', "%{$search}%");
            });
        }

        if ($angkatan) {
            $query->where('class_room.angkatan', $angkatan);
        }

        if ($prodi) {
            $query->whereHas('classRoom.prodi', function ($q) use ($prodi) {
                $q->where('prodi_name', $prodi);
            });
        }

        if ($major) {
            $query->whereHas('classRoom.prodi.major', function ($q) use ($major) {
                $q->where('major_name', $major);
            });
        }

        if ($class) {
            $query->where('class_room.class_name', $class);
        }

        $mahasiswa = $query->get()->map(function ($item, $index) {
            $item->no = $index + 1;
            return $item;
        });

        return response()->json($mahasiswa);
    }

    public function InputMahasiswa()
    {

        return Inertia::render('Dosen/InputMahasiswa');
    }

    public function exportMahasiswa(Request $request)
    {
        try {
            // Validate request
            $validator = Validator::make($request->all(), [
                'jurusan' => 'required|exists:major,id',
                'prodi' => 'required|exists:prodi,id',
                'angkatan' => 'required|integer|min:2000|max:' . (date('Y') + 1),
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            return Excel::download(
                new MahasiswaExport(
                    $request->prodi,
                    $request->angkatan
                ),
                'data-mahasiswa.xlsx'
            );
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error exporting data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function ImportMahasiswa(Request $request)
    {

        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
        ]);

        Excel::import(new MahasiswaImport, $request->file('file'));

        return redirect()->route('dosen.manage-mahasiswa')->with('success', 'Data mahasiswa berhasil diimpor!');
    }

    public function ManageDosen()
    {
        return Inertia::render('Dosen/ManageDosen');
    }

    public function DetailDosen(Request $request)
    {
        $userId = $request->input('user_id');
        $user = User::find($userId);

        if (!$user) {
            Log::warning('User not found:', ['user_id' => $userId]);
            return redirect()->back()->with('error', 'User tidak ditemukan');
        }

        Log::info('User found:', ['user_id' => $userId, 'name' => $user->name]);

        return Inertia::render('Dosen/DetailDosen', [
            'user_id' => $userId,
            'user_name' => $user->name,
        ]);
    }

    public function getProfileDosen($user_id)
    {

        $dosen = Dosen::with([
            'user',
            'major',
        ])
            ->where('user_id', $user_id)
            ->first();

        if (!$dosen) {
            return response()->json(['message' => 'Data Dosen tidak ditemukan.'], 404);
        }

        $photoUrl = $dosen->user->photo ? asset('storage/' . $dosen->user->photo) : null;

        return response()->json([
            'nama' => $dosen->user->name,
            'nip' => $dosen->nip,
            'jurusan' => $dosen->major->major_name,
            'email' => $dosen->user->email,
            'telepon' => $dosen->phone,
            'photo' => $photoUrl,
        ]);
    }


    public function getDosen(Request $request)
    {
        $query = Dosen::with(relations: ['user']);

        $dosen = $query->get();

        $dosen = $dosen->map(function ($item, $index) {
            $item->no = $index + 1;
            return $item;
        });

        return response()->json($dosen);
    }

    public function InputDosen()
    {

        return Inertia::render('Dosen/InputDosen');
    }

    public function ExportDosen(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'jurusan' => 'required|exists:major,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $majorId = $request->input('jurusan');

            return Excel::download(new DosenExport($majorId), 'Data_Dosen.xlsx');
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error exporting data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function ImportDosen(Request $request)
    {

        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
        ]);

        Excel::import(new DosenImport, $request->file('file'));

        return redirect()->route('ManageDosen')->with('success', 'Data dosen berhasil diimpor!');
    }


    public function getAngkatan()
    {
        // Ambil angkatan yang unik berdasarkan mahasiswa dengan role 'mahasiswa'
        $angkatan = Mahasiswa::join('users', 'users.id', '=', 'mahasiswa.user_id') // Join dengan tabel users
            ->join('class_room', 'class_room.id', '=', 'mahasiswa.class_id') // Join dengan tabel class_room
            ->where('users.role', 'mahasiswa') // Pastikan role adalah 'mahasiswa'
            ->distinct()
            ->pluck('class_room.angkatan'); // Ambil angkatan dari class_room

        return response()->json($angkatan);
    }

    public function getMajor()
    {
        // Ambil major yang unik berdasarkan mahasiswa dengan role 'mahasiswa'
        $major = Mahasiswa::join('users', 'users.id', '=', 'mahasiswa.user_id')
            ->join('class_room', 'class_room.id', '=', 'mahasiswa.class_id')
            ->join('prodi', 'prodi.id', '=', 'class_room.prodi_id')
            ->join('major', 'major.id', '=', 'prodi.major_id')
            ->where('users.role', 'mahasiswa')
            ->distinct()
            ->orderBy('major.major_name', 'asc')
            ->pluck('major.major_name');

        return response()->json($major);
    }

    public function getClass()
    {
        // Ambil class_name yang unik berdasarkan mahasiswa dengan role 'mahasiswa'
        $kelas = Mahasiswa::join('users', 'users.id', '=', 'mahasiswa.user_id') // Join dengan tabel users
            ->join('class_room', 'class_room.id', '=', 'mahasiswa.class_id') // Join dengan tabel class_room
            ->where('users.role', 'mahasiswa') // Pastikan role adalah 'mahasiswa'
            ->distinct()
            ->pluck('class_room.class_name'); // Ambil class_name dari class_room

        return response()->json($kelas);
    }

    public function getProdiByMajor(string $majorId)
    {
        try {
            $prodiList = Prodi::where('major_id', $majorId)
                ->select('id', 'prodi_name')
                ->orderBy('prodi_name', 'asc')
                ->get();

            if ($prodiList->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No study programs found for this major',
                    'data' => []
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Study programs retrieved successfully',
                'data' => $prodiList
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve study programs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getJurusanList()
    {
        try {
            $majors = Major::select('id', 'major_name')
                ->orderBy('major_name', 'asc')
                ->get();

            if ($majors->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No majors found',
                    'data' => []
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Majors retrieved successfully',
                'data' => $majors
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve majors',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function checkMahasiswaGroups($id)
{
    // Try to find the mahasiswa using both id and user_id
    $mahasiswa = Mahasiswa::with('user')
        ->where('id', $id)
        ->orWhere('user_id', $id)
        ->first();

    if (!$mahasiswa) {
        return response()->json([
            'error' => 'Mahasiswa tidak ditemukan'
        ], 404);
    }

    // Find groups the mahasiswa belongs to BEFORE deletion
    $groups = Group::where('mahasiswa_id', $mahasiswa->id)
        ->join('project', 'groups.project_id', '=', 'project.id')
        ->select('project.project_name', 'groups.group as group_name')
        ->get();

    return response()->json([
        'has_groups' => $groups->isNotEmpty(),
        'groups' => $groups,
        'mahasiswa_id' => $mahasiswa->id,
        'user_id' => $mahasiswa->user_id
    ]);
}

public function deleteMhs(Request $request, $id) {
    // Validasi input
    $validator = Validator::make($request->all(), [
        'force' => 'sometimes|boolean'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'errors' => $validator->errors(),
            'message' => 'Validation failed'
        ], 422);
    }

    $forceDelete = $request->input('force', false);

    DB::beginTransaction();

    try {
        // Cari mahasiswa berdasarkan user_id atau id mahasiswa
        $mahasiswa = Mahasiswa::where('id', $id)
            ->orWhere('user_id', $id)
            ->first();

        // Jika mahasiswa tidak ditemukan, coba cari di users
        if (!$mahasiswa) {
            $user = User::findOrFail($id);
            
            // Cari mahasiswa terkait dengan user
            $mahasiswa = Mahasiswa::where('user_id', $user->id)->first();
            
            if (!$mahasiswa) {
                return response()->json([
                    'error' => 'Mahasiswa tidak ditemukan',
                    'details' => 'Tidak ada data mahasiswa terkait dengan user ini'
                ], 404);
            }
        }

        // Cek relasi yang mungkin menghalangi penghapusan
        $groupsCount = DB::table('groups')
            ->where('mahasiswa_id', $mahasiswa->id)
            ->count();

        // Jika ada data groups dan bukan force delete, kembalikan peringatan
        if ($groupsCount > 0 && !$forceDelete) {
            return response()->json([
                'error' => 'Mahasiswa memiliki data group yang mencegah penghapusan.',
                'details' => [
                    'groups_count' => $groupsCount
                ],
                'requires_confirmation' => true
            ], 400);
        }

        // Jika force delete, hapus data terkait
        if ($forceDelete) {
            // Hapus groups terkait
            DB::table('groups')
                ->where('mahasiswa_id', $mahasiswa->id)
                ->delete();
        }

        // Simpan ID user untuk dihapus
        $userId = $mahasiswa->user_id;

        // Hapus data mahasiswa
        $mahasiswa->forceDelete();

        // Hapus user terkait
        User::where('id', $userId)->forceDelete();

        DB::commit();

        return response()->json([
            'message' => 'Mahasiswa berhasil dihapus',
            'deleted_mahasiswa_id' => $mahasiswa->id,
            'deleted_user_id' => $userId,
            'deleted_groups_count' => $groupsCount
        ]);

    } catch (\Exception $e) {
        DB::rollBack();

        return response()->json([
            'error' => 'Gagal menghapus mahasiswa',
            'details' => $e->getMessage()
        ], 500);
    }
}
    
}
