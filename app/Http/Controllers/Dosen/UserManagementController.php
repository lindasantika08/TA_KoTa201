<?php

namespace App\Http\Controllers\Dosen;

use App\Models\User;
use App\Models\Mahasiswa;
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


class UserManagementController extends Controller
{

    public function ManageMahasiswa()
    {

        return Inertia::render('Dosen/ManageMahasiswa');
    }

    public function DetailMahasiswa()
    {

        return Inertia::render('Dosen/DetailMahasiswa');
    }

    public function getMahasiswa(Request $request)
    {
        // Ambil parameter dari request
        $angkatan = $request->get('batch_year');
        $class = $request->get('class_name');
    
        // Mulai query untuk mengambil data mahasiswa
        $query = Mahasiswa::with(['classRoom', 'user']);
    
        // Filter berdasarkan angkatan (batch_year)
        if ($angkatan) {
            $query->whereHas('classRoom', function ($q) use ($angkatan) {
                $q->where('batch_year', $angkatan);
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


    public function ManageDosen(Request $request)
    {

        return Inertia::render('Dosen/ManageDosen');
    }

    public function DetailDosen()
    {

        return Inertia::render('Dosen/DetailDosen');
    }

    public function getDosen(Request $request)
    {
        $query = User::where('role', 'dosen');

        // Filter berdasarkan jurusan jika dipilih
        if ($request->has('jurusan') && $request->jurusan) {
            $query->where('jurusan', $request->jurusan);
        }

        $users = $query->select('id', 'name', 'kode_dosen', 'email', 'nip', 'jurusan')
            ->get();

        return response()->json($users);
    }



    public function InputDosen()
    {

        return Inertia::render('Dosen/InputDosen');
    }

    public function ExportDosen(Request $request)
    {

        return Excel::download(new DosenExport(
            $request->jurusan,
        ), 'Data_Dosen.xlsx');
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
        // Ambil batch_year yang unik berdasarkan mahasiswa dengan role 'mahasiswa'
        $angkatan = Mahasiswa::join('users', 'users.id', '=', 'mahasiswa.user_id') // Join dengan tabel users
            ->join('class_room', 'class_room.id', '=', 'mahasiswa.class_id') // Join dengan tabel class_room
            ->where('users.role', 'mahasiswa') // Pastikan role adalah 'mahasiswa'
            ->distinct()
            ->pluck('class_room.batch_year'); // Ambil batch_year dari class_room
    
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
