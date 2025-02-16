<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;
use App\Exports\DosenExport;
use App\Exports\MahasiswaExport;
use App\Imports\DosenImport;
use App\Imports\MahasiswaImport;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

use App\Models\Dosen;
use App\Models\User;

class UserAdminController extends Controller
{
    public function showManageDosen()
    {
        return Inertia::render('Admin/ManageDosen');
    }

    public function showManageMahasiswa()
    {
        return Inertia::render('Admin/ManageMahasiswa');
    }

    public function getDosen(Request $request)
    {
        // Mulai query untuk mengambil data dosen beserta relasi user dan major
        $query = Dosen::with(['user', 'major']);

        // Ambil data dosen
        $dosen = $query->get();

        // Format data agar sesuai dengan yang dibutuhkan (misalnya menambahkan nomor urut)
        $dosen = $dosen->map(function ($item, $index) {
            $item->no = $index + 1;
            $item->major_name = $item->major ? $item->major->major_name : null; // Pastikan major tidak null
            return $item;
        });

        // Kirim data ke frontend
        return response()->json($dosen);
    }

    public function InputDosen()
    {
        return Inertia::render('Admin/InputDosenExcel');
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

        return redirect()->route('/admin/ManageDosen')->with('success', 'Data dosen berhasil diimpor!');
    }

    public function InputMahasiswa()
    {

        return Inertia::render('Admin/InputMahasiswaExcel');
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

    public function deleteDosen(Request $request)
    {
        Log::info("Semua request:", $request->all());

        $request->validate([
            'nip' => 'required|string|max:21'
        ]);

        $dosen = Dosen::where('nip', $request->nip)->first();

        if (!$dosen) {
            return response()->json(['message' => 'NIP not found!'], 404);
        }

        $user = User::where('id', $dosen->user_id)->first();

        $dosen->delete(); // Soft delete dosen

        if ($user) {
            $user->delete(); // Soft delete user
        }

        return response()->json(['message' => 'Delete successfully'], 201);
    }
}
