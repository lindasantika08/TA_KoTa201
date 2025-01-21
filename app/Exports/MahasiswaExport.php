<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;

class MahasiswaExport implements FromCollection
{
    public function collection()
    {
        // Ambil data mahasiswa dengan role 'mahasiswa'
        return User::where('role', 'mahasiswa')->get(['name', 'email', 'nim']);
    }
}


