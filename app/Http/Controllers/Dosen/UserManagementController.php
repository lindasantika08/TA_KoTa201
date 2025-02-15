<?php

namespace App\Http\Controllers\Dosen;

use App\Models\User;
use App\Models\Mahasiswa;
use App\Models\Dosen;
use App\Models\Major;
use App\Models\Prodi;
use App\Models\ClassRoom;
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
        // Ambil parameter dari request
        $angkatan = $request->get('angkatan');
        $class = $request->get('class_name');
    
        // Mulai query untuk mengambil data mahasiswa
        $query = Mahasiswa::with(['classRoom', 'user']);
    
        // Filter berdasarkan angkatan (angkatan)
        if ($angkatan) {
            $query->whereHas('classRoom', function ($q) use ($angkatan) {
                $q->where('angkatan', $angkatan);
            });
        }
    
        // Filter berdasarkan kelas
        if ($class) {
            $query->whereHas('classRoom', function ($q) use ($class) {
                $q->where('class_name', $class);
            });
        }
    
        // Ambil data mahasiswa
        $mahasiswa = $query->get();
    
        // Format data agar sesuai dengan yang dibutuhkan (misalnya menambahkan nomor urut)
        $mahasiswa = $mahasiswa->map(function ($item, $index) {
            $item->no = $index + 1;
            return $item;
        });
    
        // Kirim data ke frontend
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

        return redirect()->route('ManageMahasiswa')->with('success', 'Data mahasiswa berhasil diimpor!');
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

        // Ambil data Dosen yang terkait dengan user
        $dosen = Dosen::with([
            'user',          // Relasi dengan tabel user
            'major', // Relasi dengan major
        ])
            ->where('user_id', $user_id) // Pastikan hanya mengambil data Dosen yang sesuai dengan user yang sedang login
            ->first(); // Ambil hanya satu data Dosen (karena user hanya punya satu Dosen)

        if (!$dosen) {
            return response()->json(['message' => 'Data Dosen tidak ditemukan.'], 404);
        }

        // Periksa apakah Dosen memiliki foto dan buat URL dengan asset()
        $photoUrl = $dosen->user->photo ? asset('storage/' . $dosen->user->photo) : null;

        // Kembalikan data Dosen dengan relasi terkait
        return response()->json([
            'nama' => $dosen->user->name,
            'nip' => $dosen->nip,
            'jurusan' => $dosen->major->major_name,
            'email' => $dosen->user->email,
            'telepon' => $dosen->phone, // Misalkan ada kolom telepon di tabel user
            'photo' => $photoUrl, // Menambahkan URL foto
        ]);
    }


    public function getDosen(Request $request)
    {
        // Mulai query untuk mengambil data mahasiswa
        $query = Dosen::with(relations: ['user']);

        // Ambil data dosen
        $dosen = $query->get();

        // Format data agar sesuai dengan yang dibutuhkan (misalnya menambahkan nomor urut)
        $dosen = $dosen->map(function ($item, $index) {
            $item->no = $index + 1;
            return $item;
        });

        // Kirim data ke frontend
        return response()->json($dosen);
    }

//     public function getDosen(Request $request)
// {
//     try {
//         // Dapatkan user yang sedang login
//         $user = auth()->user();
        
//         // Dapatkan major_id dari user yang login
//         // Asumsikan user memiliki relasi ke dosen dan dosen memiliki major_id
//         $majorId = $user->dosen->major_id;
        
//         // Query untuk mengambil data dosen
//         $query = Dosen::with(['user'])
//             ->where('major_id', $majorId)
//             ->orderBy('created_at', 'desc');

//         // Ambil data dosen
//         $dosen = $query->get();

//         // Format data dengan menambahkan nomor urut
//         $dosen = $dosen->map(function ($item, $index) {
//             $item->no = $index + 1;
//             return $item;
//         });

//         return response()->json([
//             'status' => 'success',
//             'data' => $dosen
//         ]);

//     } catch (\Exception $e) {
//         return response()->json([
//             'status' => 'error',
//             'message' => 'Gagal mengambil data dosen: ' . $e->getMessage()
//         ], 500);
//     }
// }



    public function InputDosen()
    {

        return Inertia::render('Dosen/InputDosen');
    }

    public function ExportDosen(Request $request)
{
    try {
        // Validate request
        $validator = Validator::make($request->all(), [
            'jurusan' => 'required|exists:major,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Get the major_id from the validated request
        $majorId = $request->input('jurusan');

        // Generate Excel file
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
}
