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
    private $currentAngkatan = null;
    private $currentJurusan = null;

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

            // Track NIM, angkatan, dan jurusan untuk proses cleanup
            $this->processedNims[] = $row['nim'];
            if (!$this->currentAngkatan) {
                $this->currentAngkatan = $row['angkatan'];
                $this->currentJurusan = $row['jurusan']; // Simpan jurusan dari data pertama
            } elseif ($this->currentAngkatan != $row['angkatan']) {
                $this->currentAngkatan = 'mixed';
            } elseif ($this->currentJurusan != $row['jurusan']) {
                // Jika ada multiple jurusan, matikan fitur cleanup
                $this->currentJurusan = 'mixed';
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
                // Log kredensial sebelum dikirim
                Log::info('Mencoba mengirim kredensial ke email:', [
                    'nama' => $row['nama_mahasiswa'],
                    'email' => $row['email'],
                    'password' => $password
                ]);

                Mail::to($row['email'])
                    ->send(new CredentialMail($row['email'], $password, $row['nama_mahasiswa']));

                // Log sukses dengan detail
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
            // Hanya hapus jika kita memproses single angkatan dan single jurusan
            if ($this->currentAngkatan && $this->currentAngkatan !== 'mixed' 
                && $this->currentJurusan && $this->currentJurusan !== 'mixed') {
                
                $mahasiswaToDelete = Mahasiswa::whereNotIn('nim', $this->processedNims)
                    ->whereHas('classRoom', function ($query) {
                        $query->where('angkatan', $this->currentAngkatan)
                              ->whereHas('prodi', function($q) {
                                  $q->whereHas('major', function($q2) {
                                      $q2->where('major_name', $this->currentJurusan);
                                  });
                              });
                    })
                    ->get();

                foreach ($mahasiswaToDelete as $mhs) {
                    Log::info('Menghapus mahasiswa', [
                        'id' => $mhs->id,
                        'nim' => $mhs->nim,
                        'angkatan' => $this->currentAngkatan,
                        'jurusan' => $this->currentJurusan
                    ]);
                    $mhs->delete();
                }
            } else {
                Log::info('Melewati proses penghapusan karena multiple angkatan atau jurusan terdeteksi');
            }
        } catch (\Exception $e) {
            Log::error('Kesalahan saat menghapus mahasiswa', [
                'error' => $e->getMessage(),
                'jurusan' => $this->currentJurusan,
                'angkatan' => $this->currentAngkatan
            ]);
        }
    }
}
