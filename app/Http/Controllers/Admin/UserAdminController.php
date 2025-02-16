<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Illuminate\Http\Request;

use App\Models\Dosen;

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
}
