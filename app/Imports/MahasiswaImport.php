<?php

namespace App\Imports;

use App\Models\User;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Log;

class MahasiswaImport implements ToModel, WithHeadingRow
{

    public function model(array $row)
    {
        try {
            // Log raw row data untuk debugging
            Log::info('Memproses baris import: ', $row);

            // Periksa kolom yang diperlukan
            $requiredColumns = ['nim', 'email', 'name'];
            foreach ($requiredColumns as $column) {
                if (empty($row[$column])) {
                    Log::warning("Kolom {$column} kosong. Melewati baris.");
                    return null;
                }
            }

            // Generate password random 8 karakter
            $password = Str::random(8);

            // Buat user baru
            $user = new User([
                'id' => Str::uuid(), // Tambahkan UUID untuk ID
                'name' => $row['name'],
                'email' => $row['email'],
                'password' => bcrypt($password),
                'nim' => $row['nim'],
                'role' => 'mahasiswa',
                'jurusan' => $row['jurusan'] ?? null,
                'prodi' => $row['prodi'] ?? null,
                'angkatan' => $row['angkatan'] ?? null,
                'class' => $row['class'] ?? null,

            ]);

            // Log informasi user yang dibuat
            Log::info('User berhasil dibuat: ', [
                'id' => $user->id,
                'password' => $user->password,
                'role' => $user->role,
                'nim' => $user->nim, 
                'email' => $user->email
            ]);

            return $user;

        } catch (\Exception $e) {
            // Tangani error saat memproses baris
            Log::error('Kesalahan saat memproses baris import: ', [
                'row' => $row,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return null;
        }
    }
}