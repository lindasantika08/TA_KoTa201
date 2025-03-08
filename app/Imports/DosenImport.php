<?php

namespace App\Imports;

use App\Models\User;
use App\Models\Dosen;
use App\Models\Major;
use App\Mail\CredentialMail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Row;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DosenImport implements ToModel, WithHeadingRow, WithBatchInserts, OnEachRow
{
    private $processedNips = [];
    private $majors = [];
    private $currentMajorId = null;

    public function __construct()
    {
        $this->majors = Major::pluck('id', 'major_name')->toArray();
    }

    public function model(array $row)
    {
        try {
            Log::info('Memproses baris import: ', $row);

            // Periksa kolom yang diperlukan
            $requiredColumns = ['nip', 'email', 'name', 'kode_dosen', 'jurusan'];
            foreach ($requiredColumns as $column) {
                if (empty($row[$column])) {
                    Log::warning("Kolom {$column} kosong. Melewati baris.");
                    return null;
                }
            }

            // Validasi major_id
            $majorId = $this->majors[$row['jurusan']] ?? null;
            if (!$majorId) {
                Log::warning("Jurusan tidak valid: {$row['jurusan']}");
                return null;
            }

            $this->currentMajorId = $majorId;

            DB::beginTransaction();
            try {
                // Generate password
                $password = Str::random(8);
                // Cari dosen berdasarkan kode_dosen (sebagai identifier unik)
                $existingDosen = Dosen::where('kode_dosen', $row['kode_dosen'])->first();

                // Jika tidak ditemukan dengan kode_dosen, cari berdasarkan NIP
                if (!$existingDosen) {
                    $existingDosen = Dosen::where('nip', $row['nip'])->first();
                }

                // Cek user berdasarkan email
                $existingUser = User::where('email', $row['email'])->first();

                if ($existingDosen) {
                    // Update user yang terkait
                    if ($existingUser) {
                        $existingUser->update([
                            'name' => $row['name'],
                            'email' => $row['email'],
                            'role' => 'dosen'
                        ]);
                    } else {
                        // Buat user baru jika belum ada
                        $existingUser = User::create([
                            'id' => Str::uuid(),
                            'name' => $row['name'],
                            'email' => $row['email'],
                            'password' => bcrypt($password),
                            'role' => 'dosen'
                        ]);
                    }

                    // Update data dosen
                    $existingDosen->update([
                        'user_id' => $existingUser->id,
                        'nip' => $row['nip'],
                        'major_id' => $majorId,
                        'kode_dosen' => $row['kode_dosen']
                    ]);

                    // Tambahkan NIP baru ke daftar yang telah diproses
                    $this->processedNips[$row['nip']] = $majorId;
                } else {
                    // Buat data baru jika tidak ditemukan
                    if (!$existingUser) {
                        $existingUser = User::create([
                            'id' => Str::uuid(),
                            'name' => $row['name'],
                            'email' => $row['email'],
                            'password' => bcrypt($password),
                            'role' => 'dosen'
                        ]);
                    }

                    // Kirim email kredensial
                    try {
                        // Log kredensial sebelum dikirim
                        Log::info('Mencoba mengirim kredensial ke email:', [
                            'nama' => $row['name'],
                            'email' => $row['email'],
                            'password' => $password
                        ]);

                        Mail::to($row['email'])
                            ->send(new CredentialMail($row['email'], $password, $row['name']));

                        // Log sukses dengan detail
                        Log::info('Email kredensial berhasil dikirim', [
                            'nama' => $row['name'],
                            'email' => $row['email'],
                            'password' => $password,
                            'status' => 'success',
                            'waktu_kirim' => now()->format('Y-m-d H:i:s')
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Gagal mengirim email kredensial', [
                            'nama' => $row['name'],
                            'email' => $row['email'],
                            'password' => $password,
                            'error' => $e->getMessage(),
                            'status' => 'failed',
                            'waktu_error' => now()->format('Y-m-d H:i:s')
                        ]);
                    }

                    $newDosen = Dosen::create([
                        'user_id' => $existingUser->id,
                        'major_id' => $majorId,
                        'nip' => $row['nip'],
                        'kode_dosen' => $row['kode_dosen']
                    ]);

                    // Tambahkan NIP baru ke daftar yang telah diproses
                    $this->processedNips[] = $row['nip'];
                }

                DB::commit();
                return null;
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
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
            $currentUser = Auth::user();

            // Tambahkan logging untuk debug
            Log::info('Processed NIPs:', $this->processedNips);
            Log::info('Current Major ID:', [$this->currentMajorId]);

            // Validasi data sebelum proses
            if (empty($this->processedNips) || !$this->currentMajorId) {
                Log::warning('Tidak ada data valid untuk diproses atau major_id tidak valid');
                return;
            }

            // Tambahkan NIP user yang sedang login ke daftar pengecualian
            if ($currentUser && $currentUser->dosen) {
                $this->processedNips[$currentUser->dosen->nip] = $currentUser->dosen->major_id;
            }

            // Dapatkan dosen yang akan dihapus (hanya dari jurusan yang sama)
            $dosensToDelete = Dosen::where('major_id', $this->currentMajorId)
                ->whereNotIn('nip', array_keys($this->processedNips))
                ->get();

            // Debug log
            Log::info('Dosen yang akan dihapus:', $dosensToDelete->pluck('nip')->toArray());

            // Tambahkan pengecekan waktu
            $recentlyCreated = now()->subMinutes(5);

            DB::transaction(function () use ($dosensToDelete, $recentlyCreated) {
                foreach ($dosensToDelete as $dosen) {
                    // Jangan hapus dosen yang baru dibuat
                    if ($dosen->created_at >= $recentlyCreated) {
                        Log::info('Melewati penghapusan dosen baru:', [
                            'nip' => $dosen->nip,
                            'created_at' => $dosen->created_at
                        ]);
                        continue;
                    }

                    try {
                        $userId = $dosen->user_id;

                        Log::info('Menghapus dosen:', [
                            'dosen_id' => $dosen->id,
                            'nip' => $dosen->nip,
                            'created_at' => $dosen->created_at
                        ]);

                        $dosen->delete();

                        // Cek apakah user memiliki dosen lain
                        $userHasOtherDosen = Dosen::where('user_id', $userId)
                            ->where('id', '!=', $dosen->id)
                            ->exists();

                        if (!$userHasOtherDosen) {
                            $user = User::find($userId);
                            if ($user) {
                                $user->delete();
                            }
                        }
                    } catch (\Exception $e) {
                        Log::error('Gagal menghapus dosen/user:', [
                            'dosen_id' => $dosen->id,
                            'error' => $e->getMessage()
                        ]);
                        throw $e;
                    }
                }
            });
        } catch (\Exception $e) {
            Log::error('Error in destructor:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
