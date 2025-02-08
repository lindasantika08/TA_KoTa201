<?php

namespace App\Imports;

use App\Models\User;
use App\Models\Dosen;
use App\Models\Major;
use Illuminate\Support\Str;
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

    public function __construct()
    {
        $this->majors = Major::pluck('id', 'major_name')->toArray();
        // Simpan data NIP yang ada sebelum import
        // $this->oldNipMapping = Dosen::pluck('nip', 'id')->toArray();
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

            DB::beginTransaction();
            try {
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
                            'password' => bcrypt('qwert1234'),
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
                    $this->processedNips[] = $row['nip'];
                } else {
                    // Buat data baru jika tidak ditemukan
                    if (!$existingUser) {
                        $existingUser = User::create([
                            'id' => Str::uuid(),
                            'name' => $row['name'],
                            'email' => $row['email'],
                            'password' => bcrypt('qwert1234'),
                            'role' => 'dosen'
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

            Log::info('NIPs yang diproses:', ['nips' => $this->processedNips]);

            // Hanya hapus data yang benar-benar tidak ada di file import
            // dan bukan milik user yang sedang login
            if ($currentUser && $currentUser->dosen) {
                $this->processedNips[] = $currentUser->dosen->nip;
            }

            // PENTING: Jangan hapus data jika tidak ada NIP yang diproses
            if (empty($this->processedNips)) {
                Log::warning('Tidak ada NIP yang diproses, membatalkan penghapusan');
                return;
            }

            // Dapatkan dosen yang akan dihapus
            $dosensToDelete = Dosen::whereNotIn('nip', $this->processedNips)
                                 ->get();

            DB::transaction(function () use ($dosensToDelete) {
                foreach ($dosensToDelete as $dosen) {
                    try {
                        $userId = $dosen->user_id;

                        // Log sebelum penghapusan
                        Log::info('Menghapus dosen:', [
                            'dosen_id' => $dosen->id,
                            'nip' => $dosen->nip,
                            'kode_dosen' => $dosen->kode_dosen
                        ]);

                        $dosen->delete(); // Gunakan soft delete

                        // Cek apakah user memiliki dosen lain
                        $userHasOtherDosen = Dosen::where('user_id', $userId)
                            ->where('id', '!=', $dosen->id)
                            ->exists();

                        if (!$userHasOtherDosen) {
                            $user = User::find($userId);
                            if ($user) {
                                $user->delete(); // Gunakan soft delete
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
            Log::error('Kesalahan saat menghapus dosen:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            // Tidak throw exception di destructor
            Log::error('Error in destructor: ' . $e->getMessage());
        }
    }
}