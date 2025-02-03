<?php

namespace App\Imports;

use App\Models\User;
use App\Models\Mahasiswa;
use App\Models\ClassRoom;
use App\Models\Prodi;
use App\Models\Major;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\WithBatchInserts;

class MahasiswaImport implements ToModel, WithHeadingRow, WithBatchInserts
{
    private $processedNims = [];

    public function model(array $row)
    {
        try {
            // Validasi data wajib
            $requiredColumns = ['nim', 'email', 'nama_mahasiswa', 'jurusan', 'prodi', 'angkatan', 'kelas'];
            foreach ($requiredColumns as $column) {
                if (empty($row[$column])) {
                    Log::warning("Kolom {$column} kosong. Melewati baris.");
                    return null;
                }
            }

            $this->processedNims[] = $row['nim'];

            // Cek atau buat Major
            $major = Major::firstOrCreate(['major_name' => $row['jurusan']]);

            // Cek atau buat Prodi
            $prodi = Prodi::firstOrCreate([
                'major_id' => $major->id,
                'prodi_name' => $row['prodi']
            ]);

            // Cek atau buat ClassRoom
            $classRoom = ClassRoom::firstOrCreate([
                'class_name' => $row['kelas'],
                'prodi_id' => $prodi->id,
                'angkatan' => $row['angkatan']
            ]);

            // Cek apakah user sudah ada
            $user = User::where('email', $row['email'])->first();

            if ($user) {
                // Cek apakah ada perubahan data
                $updates = [];
                if ($user->name !== $row['nama_mahasiswa']) $updates['name'] = $row['nama_mahasiswa'];
                if ($user->email !== $row['email']) $updates['email'] = $row['email'];

                if (!empty($updates)) {
                    $user->update($updates);
                    Log::info('User diperbarui', ['id' => $user->id, 'updates' => $updates]);
                }
            } else {
                // Buat user baru
                $user = User::create([
                    'id' => Str::uuid(),
                    'name' => $row['nama_mahasiswa'],
                    'email' => $row['email'],
                    'password' => bcrypt('qwert1234'),
                    'role' => 'mahasiswa'
                ]);
            }

            // Cek atau buat Mahasiswa
            Mahasiswa::updateOrCreate([
                'nim' => $row['nim']
            ], [
                'user_id' => $user->id,
                'class_id' => $classRoom->id,
                'nim' => $row['nim']
            ]);

            Log::info('Mahasiswa diperbarui atau dibuat', ['nim' => $row['nim']]);
        } catch (\Exception $e) {
            Log::error('Kesalahan saat memproses baris import', [
                'row' => $row,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    public function batchSize(): int
    {
        return 1000;
    }

    public function __destruct()
    {
        try {
            // Hapus mahasiswa yang tidak ada dalam file import
            $deletedMahasiswa = Mahasiswa::whereNotIn('nim', $this->processedNims)->get();
            foreach ($deletedMahasiswa as $mhs) {
                Log::info('Menghapus mahasiswa', ['id' => $mhs->id, 'nim' => $mhs->nim]);
                $mhs->delete();
            }
        } catch (\Exception $e) {
            Log::error('Kesalahan saat menghapus mahasiswa', ['error' => $e->getMessage()]);
        }
    }
}
