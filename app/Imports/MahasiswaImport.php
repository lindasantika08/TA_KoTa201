<?php

namespace App\Imports;

use App\Models\User;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Row;

class MahasiswaImport implements ToModel, WithHeadingRow, WithBatchInserts, OnEachRow
{
    private $processedNims = [];

    public function model(array $row)
    {
        try {
            Log::info('Memproses baris import: ', $row);

            // Periksa kolom yang diperlukan
            $requiredColumns = ['nim', 'email', 'name'];
            foreach ($requiredColumns as $column) {
                if (empty($row[$column])) {
                    Log::warning("Kolom {$column} kosong. Melewati baris.");
                    return null;
                }
            }

            // Tambahkan NIM ke daftar yang telah diproses
            $this->processedNims[] = $row['nim'];

            // Cek apakah user dengan NIM atau email sudah ada
            $existingUser = User::where('nim', $row['nim'])
                              ->orWhere('email', $row['email'])
                              ->first();

            if ($existingUser) {
                // Jika user sudah ada, cek perubahan data
                $hasChanges = false;
                $updates = [];

                // Daftar field yang akan dicek perubahannya
                $fieldsToCheck = [
                    'nim' => $row['nim'],
                    'name' => $row['name'],
                    'email' => $row['email'],
                    'jurusan' => $row['jurusan'] ?? null,
                    'prodi' => $row['prodi'] ?? null,
                    'angkatan' => $row['angkatan'] ?? null,
                    'class' => $row['class'] ?? null,
                ];

                foreach ($fieldsToCheck as $field => $newValue) {
                    if ($existingUser->$field !== $newValue) {
                        $hasChanges = true;
                        $updates[$field] = $newValue;
                    }
                }

                if ($hasChanges) {
                    // Update data jika ada perubahan
                    $existingUser->update($updates);
                    Log::info('User diperbarui: ', [
                        'id' => $existingUser->id,
                        'updates' => $updates
                    ]);
                } else {
                    Log::info('Tidak ada perubahan untuk user: ', [
                        'id' => $existingUser->id
                    ]);
                }

                return null; // Kembalikan null karena tidak perlu membuat model baru
            }

            // Jika user belum ada, buat user baru
            $password = Str::random(8);
            $user = new User([
                'id' => Str::uuid(),
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

            Log::info('User baru dibuat: ', [
                'id' => $user->id,
                'nim' => $user->nim,
                'email' => $user->email
            ]);

            return $user;

        } catch (\Exception $e) {
            Log::error('Kesalahan saat memproses baris import: ', [
                'row' => $row,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return null;
        }
    }

    public function onRow(Row $row)
    {
        // Method ini diperlukan untuk interface OnEachRow
        // Kita bisa meninggalkannya kosong karena logika utama ada di method model()
        return null;
    }

    public function batchSize(): int
    {
        return 1000;
    }

    public function __destruct()
    {
        try {
            // Hapus user yang NIM-nya tidak ada dalam data import terbaru
            $deletedUsers = User::where('role', 'mahasiswa')
                ->whereNotIn('nim', $this->processedNims)
                ->get();

            foreach ($deletedUsers as $user) {
                Log::info('Menghapus user yang tidak ada dalam import: ', [
                    'id' => $user->id,
                    'nim' => $user->nim,
                    'email' => $user->email
                ]);
            }

            User::where('role', 'mahasiswa')
                ->whereNotIn('nim', $this->processedNims)
                ->delete();

        } catch (\Exception $e) {
            Log::error('Kesalahan saat menghapus user: ', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}