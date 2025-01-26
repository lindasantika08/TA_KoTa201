<?php

namespace App\Imports;

use App\Models\User;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Log;

class DosenImport implements ToModel, WithHeadingRow
{

    public function model(array $row)
    {
        try {
            // Log raw row data untuk debugging
            Log::info('Memproses baris import: ', $row);

            // Periksa kolom yang diperlukan
            $requiredColumns = ['nip', 'email', 'name', 'kode_dosen'];
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
                'kode_dosen' => $row['kode_dosen'],
                'email' => $row['email'],
                'password' => bcrypt($password),
                'nip' => $row['nip'],
                'role' => 'dosen',
                'jurusan' => $row['jurusan'] ?? null,

            ]);

            // Log informasi user yang dibuat
            Log::info('User berhasil dibuat: ', [
                'id' => $user->id,
                'password' => $user->password,
                'role' => $user->role,
                'nip' => $user->nip, 
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