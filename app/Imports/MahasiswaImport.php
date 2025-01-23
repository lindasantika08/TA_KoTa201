<?php

namespace App\Imports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class MahasiswaImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        return new User([
            'name' => $row['name'],
            'email' => $row['email'],
            'nim' => $row['nim'],
            'role' => 'mahasiswa', // Set role mahasiswa
            'password' => bcrypt('password123'), // Password default (boleh disesuaikan)
        ]);
    }
}
