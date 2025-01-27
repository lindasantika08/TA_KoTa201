<?php

namespace App\Http\Controllers\Dosen;

use App\Models\User;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\MahasiswaExport;
use App\Imports\MahasiswaImport;
use App\Exports\DosenExport;
use App\Imports\DosenImport;
use Inertia\Inertia;
use App\Http\Controllers\Controller;


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
        $query = User::where('role', 'mahasiswa');

        // Filter berdasarkan angkatan jika dipilih
        if ($request->has('angkatan') && $request->angkatan) {
            $query->where('angkatan', $request->angkatan);
        }

        // Filter berdasarkan jurusan jika dipilih
        if ($request->has('jurusan') && $request->jurusan) {
            $query->where('jurusan', $request->jurusan);
        }
        
        // Filter berdasarkan prodi jika dipilih
        if ($request->has('prodi') && $request->prodi) {
            $query->where('prodi', $request->prodi);
        }

        // Filter berdasarkan class jika dipilih
        if ($request->has('class') && $request->class) {
            $query->where('class', $request->class);
        }

        $users = $query->select('id', 'name', 'email', 'nim', 'jurusan', 'prodi', 'angkatan', 'class')
            ->get();

        return response()->json($users);
    }

   

    public function InputMahasiswa()
    {

        return Inertia::render('Dosen/InputMahasiswa');
    }

    public function ExportMahasiswa(Request $request)
    {

        return Excel::download(new MahasiswaExport(
            $request->jurusan,
            $request->prodi,
            $request->angkatan
        ), 'Data_Mahasiswa.xlsx');
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
        $angkatan = User::where('role', 'mahasiswa')
            ->distinct()
            ->pluck('angkatan');

        return response()->json($angkatan);
    }

    public function getClass()
    {
        $angkatan = User::where('role', 'mahasiswa')
            ->distinct()
            ->pluck('class');

        return response()->json($angkatan);
    }

    public function getJurusanList()
    {
        $jurusanList = [
            [
                'jurusan' => "Teknik Sipil",
                'prodi' => [
                    "D-3 Teknik Konstruksi Sipil",
                    "D-3 Teknik Konstruksi Gedung",
                    "D-4 Teknik Perancangan Jalan dan Jembatan",
                    "D-4 Teknik Perawatan dan Perbaikan Gedung",
                    "S-2 Rekayasa Infrastruktur",
                ],
            ],
            [
                'jurusan' => "Teknik Mesin",
                'prodi' => [
                    "D-3 Teknik Mesin",
                    "D-3 Teknik Aeronautika",
                    "D-4 Teknik Perancangan dan Konstruksi Mesin",
                    "D-4 Proses Manufaktur",
                ],
            ],
            [
                'jurusan' => "Teknik Refrigasi dan Tata Udara",
                'prodi' => [
                    "D-3 Teknik Pendingin dan Tata Udara",
                    "D-4 Teknik Pendingin dan Tata Udara",
                ],
            ],
            [
                'jurusan' => "Teknik Konversi Energi",
                'prodi' => [
                    "D-3 Teknik Konversi Energi",
                    "D-4 Teknologi Pembangkit Tenaga Listrik",
                    "D-4 Teknik Konservasi Energi",
                ],
            ],
            [
                'jurusan' => "Teknik Elektro",
                'prodi' => [
                    "D-3 Teknik Elektronika",
                    "D-3 Teknik Listrik",
                    "D-3 Teknik Telekomunikasi",
                    "D-4 Teknik Elektronika",
                    "D-4 Teknik Telekomunikasi",
                    "D-4 Teknik Otomasi Industri",
                ],
            ],
            [
                'jurusan' => "Teknik Kimia",
                'prodi' => [
                    "D-3 Teknik Kimia",
                    "D-3 Analis Kimia",
                    "D-4 Teknik Kimia Produksi Bersih",
                ],
            ],
            [
                'jurusan' => "Teknik Komputer dan Informatika",
                'prodi' => [
                    "D-3 Teknik Informatika",
                    "D-4 Teknik Informatika"
                ],
            ],
            [
                'jurusan' => "Akuntansi",
                'prodi' => [
                    "D-3 Akuntansi",
                    "D-3 Keuangan dan Perbankan",
                    "D-4 Akuntansi Manajemen Pemerintahan",
                    "D-4 Akuntansi",
                    "D-4 Keuangan Syariah",
                    "S-2 Keuangan & Perbankan Syariah",
                ],
            ],
            [
                'jurusan' => "Administrasi Niaga",
                'prodi' => [
                    "D-3 Administrasi Bisnis",
                    "D-3 Manajemen Pemasaran",
                    "D-3 Usaha Perjalanan Wisata",
                    "D-3 Manajemen Aset",
                    "D-4 Manajemen Aset",
                    "D-4 Administrasi Bisnis",
                    "D-4 Manajemen Pemasaran",
                    "D-4 Destinasi Pariwisata",
                ],
            ],
            [
                'jurusan' => "Bahasa Inggris",
                'prodi' => [
                    "D-3 Bahasa Inggris"
                ],
            ],
        ];

        return response()->json($jurusanList);
    }
}
