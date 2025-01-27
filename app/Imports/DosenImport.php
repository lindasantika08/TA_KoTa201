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
use Illuminate\Support\Facades\Auth;

class DosenImport implements ToModel, WithHeadingRow, WithBatchInserts, OnEachRow
{
    private $processedNips = [];

    public function model(array $row)
    {
        try {
            Log::info('Memproses baris import: ', $row);

            // Periksa kolom yang diperlukan
            $requiredColumns = ['nip', 'email', 'name', 'kode_dosen'];
            foreach ($requiredColumns as $column) {
                if (empty($row[$column])) {
                    Log::warning("Kolom {$column} kosong. Melewati baris.");
                    return null;
                }
            }

            // Tambahkan NIP ke daftar yang telah diproses
            $this->processedNips[] = $row['nip'];

            // Cek apakah dosen dengan NIP atau email sudah ada
            $existingUser = User::where('nip', $row['nip'])
                              ->orWhere('email', $row['email'])
                              ->first();

            if ($existingUser) {
                // Jika dosen sudah ada, cek perubahan data
                $hasChanges = false;
                $updates = [];

                // Daftar field yang akan dicek perubahannya
                $fieldsToCheck = [
                    'nip' => $row['nip'],
                    'email' => $row['email'],
                    'name' => $row['name'],
                    'kode_dosen' => $row['kode_dosen'],
                    'jurusan' => $row['jurusan'] ?? null,
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
                    Log::info('Dosen diperbarui: ', [
                        'id' => $existingUser->id,
                        'updates' => $updates
                    ]);
                } else {
                    Log::info('Tidak ada perubahan untuk dosen: ', [
                        'id' => $existingUser->id
                    ]);
                }

                return null; // Kembalikan null karena tidak perlu membuat model baru
            }

            // Generate UUID untuk user baru
            $uuid = Str::uuid()->toString();

            // Jika dosen belum ada, buat dosen baru
            $password = Str::random(8);
            
            // Buat instance User baru dengan UUID yang telah di-generate
            $user = new User();
            $user->id = $uuid; // Set UUID secara eksplisit
            $user->name = $row['name'];
            $user->kode_dosen = $row['kode_dosen'];
            $user->email = $row['email'];
            $user->password = bcrypt($password);
            $user->nip = $row['nip'];
            $user->role = 'dosen';
            $user->jurusan = $row['jurusan'] ?? null;

            Log::info('Dosen baru dibuat: ', [
                'id' => $user->id,
                'nip' => $user->nip,
                'kode_dosen' => $user->kode_dosen,
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
        return null;
    }

    public function batchSize(): int
    {
        return 1000;
    }

    public function __destruct()
    {
        try {
            // Get current logged in user
            $currentUser = Auth::user();

            // Query untuk mendapatkan dosen yang akan dihapus
            $query = User::where('role', 'dosen')
                ->whereNotIn('nip', $this->processedNips);

            // Jika ada user yang login, exclude dari penghapusan
            if ($currentUser) {
                $query->where('id', '!=', $currentUser->id);
            }

            // Ambil data dosen yang akan dihapus untuk logging
            $deletedUsers = $query->get();

            foreach ($deletedUsers as $user) {
                Log::info('Menghapus dosen yang tidak ada dalam import: ', [
                    'id' => $user->id,
                    'nip' => $user->nip,
                    'kode_dosen' => $user->kode_dosen,
                    'email' => $user->email
                ]);
            }

            // Eksekusi penghapusan dengan query yang sama
            $query->delete();

        } catch (\Exception $e) {
            Log::error('Kesalahan saat menghapus dosen: ', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}