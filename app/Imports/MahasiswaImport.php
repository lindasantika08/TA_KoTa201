<?php

namespace App\Imports;

use App\Models\User;
use App\Models\Mahasiswa;
use App\Models\ClassRoom;
use App\Models\Prodi;
use App\Models\Major;
use App\Mail\CredentialMail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\WithBatchInserts;

class MahasiswaImport implements ToModel, WithHeadingRow, WithBatchInserts
{
    private $processedNims = [];
    private $processedData = [];
    private $currentAngkatan = null;
    private $currentProdi = null;

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

            // Track NIM, angkatan, dan prodi untuk proses cleanup
            $this->processedNims[] = $row['nim'];

            // Track combination of angkatan and prodi
            $this->processedData[] = [
                'angkatan' => $row['angkatan'],
                'prodi' => $row['prodi']
            ];

            // Track current angkatan
            if (!$this->currentAngkatan) {
                $this->currentAngkatan = $row['angkatan'];
            } elseif ($this->currentAngkatan != $row['angkatan']) {
                $this->currentAngkatan = 'mixed';
            }

            // Track current prodi
            if (!$this->currentProdi) {
                $this->currentProdi = $row['prodi'];
            } elseif ($this->currentProdi != $row['prodi']) {
                $this->currentProdi = 'mixed';
            }

            // Generate password
            $password = Str::random(8);

            // Cek atau buat Major berdasarkan nama jurusan
            $major = Major::firstOrCreate(
                ['major_name' => $row['jurusan']],
                ['major_name' => $row['jurusan']]
            );

            // Cek atau buat Prodi dengan kondisi major_id dan nama prodi
            $prodi = Prodi::firstOrCreate(
                [
                    'major_id' => $major->id,
                    'prodi_name' => $row['prodi']
                ],
                [
                    'major_id' => $major->id,
                    'prodi_name' => $row['prodi']
                ]
            );

            // Cek atau buat ClassRoom dengan mempertimbangkan semua kriteria
            $classRoom = ClassRoom::firstOrCreate(
                [
                    'class_name' => $row['kelas'],
                    'prodi_id' => $prodi->id,
                    'angkatan' => $row['angkatan']
                ],
                [
                    'class_name' => $row['kelas'],
                    'prodi_id' => $prodi->id,
                    'angkatan' => $row['angkatan']
                ]
            );

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
                    'password' => bcrypt($password),
                    'role' => 'mahasiswa'
                ]);
            }

            // Kirim email kredensial
            try {
                Log::info('Mencoba mengirim kredensial ke email:', [
                    'nama' => $row['nama_mahasiswa'],
                    'email' => $row['email'],
                    'password' => $password
                ]);

                Mail::to($row['email'])
                    ->send(new CredentialMail($row['email'], $password, $row['nama_mahasiswa']));

                Log::info('Email kredensial berhasil dikirim', [
                    'nama' => $row['nama_mahasiswa'],
                    'email' => $row['email'],
                    'password' => $password,
                    'status' => 'success',
                    'waktu_kirim' => now()->format('Y-m-d H:i:s')
                ]);
            } catch (\Exception $e) {
                Log::error('Gagal mengirim email kredensial', [
                    'nama' => $row['nama_mahasiswa'],
                    'email' => $row['email'],
                    'password' => $password,
                    'error' => $e->getMessage(),
                    'status' => 'failed',
                    'waktu_error' => now()->format('Y-m-d H:i:s')
                ]);
            }

            // Cek atau buat Mahasiswa
            Mahasiswa::updateOrCreate(
                ['nim' => $row['nim']],
                [
                    'user_id' => $user->id,
                    'class_id' => $classRoom->id,
                    'nim' => $row['nim']
                ]
            );

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
            // Hanya hapus jika kita memproses single angkatan dan single prodi
            if (
                $this->currentAngkatan && $this->currentProdi &&
                $this->currentAngkatan !== 'mixed' && $this->currentProdi !== 'mixed'
            ) {

                // Get unique combinations of angkatan and prodi from processed data
                $uniqueCombinations = collect($this->processedData)->unique()->values()->all();

                foreach ($uniqueCombinations as $combination) {
                    // Ambil ID prodi berdasarkan nama
                    $prodiId = Prodi::where('prodi_name', $combination['prodi'])->value('id');

                    if (!$prodiId) {
                        Log::warning('Prodi tidak ditemukan', ['prodi_name' => $combination['prodi']]);
                        continue;
                    }

                    // Ambil mahasiswa yang perlu dihapus berdasarkan angkatan dan prodi
                    $mahasiswaToDelete = Mahasiswa::whereNotIn('nim', $this->processedNims)
                        ->whereHas('classRoom', function ($query) use ($combination, $prodiId) {
                            $query->where('angkatan', $combination['angkatan'])
                                ->where('prodi_id', $prodiId);
                        })
                        ->get();

                    foreach ($mahasiswaToDelete as $mhs) {
                        Log::info('Menghapus mahasiswa', [
                            'id' => $mhs->id,
                            'nim' => $mhs->nim,
                            'angkatan' => $combination['angkatan'],
                            'prodi' => $combination['prodi']
                        ]);
                        $mhs->delete(); // Soft delete karena model menggunakan SoftDeletes
                    }
                }
            } else {
                Log::info('Melewati proses penghapusan karena multiple angkatan atau prodi terdeteksi');
            }
        } catch (\Exception $e) {
            Log::error('Kesalahan saat menghapus mahasiswa', ['error' => $e->getMessage()]);
        }
    }
}
