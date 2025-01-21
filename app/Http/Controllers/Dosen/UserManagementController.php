<?php

namespace App\Http\Controllers\Dosen;

use App\Models\User;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\MahasiswaExport;
use App\Imports\MahasiswaImport;
use Inertia\Inertia;
use App\Http\Controllers\Controller;


class UserManagementController extends Controller
{

    public function KelolaMahasiswa()
    {
        return Inertia::render('Dosen/KelolaMahasiswa');
    }

    public function InputMahasiswa()
    {
        return Inertia::render('Dosen/InputMahasiswa');
    }

    public function ExportMahasiswa(Request $request)
    {
        // Export mahasiswa dengan hanya menampilkan nama, email, dan NIM
    return Excel::download(new MahasiswaExport, 'mahasiswa.xlsx');
    }

    public function ImportMahasiswa(Request $request)
    {
       // Validasi file
    $request->validate([
        'file' => 'required|mimes:xlsx,xls,csv',
    ]);

    // Import file
    Excel::import(new MahasiswaImport, $request->file('file'));

    return redirect()->route('KelolaMahasiswa')->with('success', 'Data mahasiswa berhasil diimpor!');
    }

    public function KelolaDosen(Request $request)
    {
        return Inertia::render('Dosen/KelolaDosen');
    }
}
