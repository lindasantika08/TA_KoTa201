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
        // Cache data major
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

            // Tambahkan NIP ke daftar yang telah diproses
            $this->processedNips[] = $row['nip'];

            DB::beginTransaction();
            try {
                // Cek dosen berdasarkan NIP
                $existingDosen = Dosen::where('nip', $row['nip'])->first();

                // Cek user berdasarkan email
                $existingUser = User::where('email', $row['email'])->first();

                if ($existingDosen || $existingUser) {
                    // Update data yang sudah ada
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

                    if ($existingDosen) {
                        $existingDosen->update([
                            'user_id' => $existingUser->id,
                            'nip' => $row['nip'],
                            'major_id' => $majorId,
                            'kode_dosen' => $row['kode_dosen']
                        ]);
                    } else {
                        // Buat data dosen baru
                        Dosen::create([
                            'user_id' => $existingUser->id,
                            'major_id' => $majorId,
                            'nip' => $row['nip'],
                            'kode_dosen' => $row['kode_dosen']
                        ]);
                    }

                    DB::commit();
                    return null;
                }

                // Buat user dan dosen baru jika keduanya belum ada
                $user = User::create([
                    'id' => Str::uuid(),
                    'name' => $row['name'],
                    'email' => $row['email'],
                    'password' => bcrypt('qwert1234'),
                    'role' => 'dosen'
                ]);

                Dosen::create([
                    'user_id' => $user->id,
                    'major_id' => $majorId,
                    'nip' => $row['nip'],
                    'kode_dosen' => $row['kode_dosen']
                ]);

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

            // Log NIPs yang diproses untuk debugging
            Log::info('NIPs yang diproses:', ['nips' => $this->processedNips]);

            // Dapatkan ID dosen yang harus dipertahankan
            $preservedDosenIds = Dosen::whereIn('nip', $this->processedNips)
                ->pluck('id')
                ->toArray();

            // Tambahkan ID dosen pengguna saat ini jika ada
            if ($currentUser && $currentUser->dosen) {
                $preservedDosenIds[] = $currentUser->dosen->id;
            }

            // Log ID yang dipertahankan untuk debugging
            Log::info('ID Dosen yang dipertahankan:', ['ids' => $preservedDosenIds]);

            // Dapatkan record dosen yang akan dihapus
            $dosensToDelete = Dosen::whereNotIn('id', $preservedDosenIds)->get();

            // Log dosen yang akan dihapus
            foreach ($dosensToDelete as $dosen) {
                Log::info('Akan menghapus dosen:', [
                    'dosen_id' => $dosen->id,
                    'user_id' => $dosen->user_id,
                    'nip' => $dosen->nip,
                    'nama' => $dosen->user ? $dosen->user->name : 'Tidak ada User',
                    'kode_dosen' => $dosen->kode_dosen
                ]);
            }

            // Hapus dosen dan user terkait
            DB::transaction(function () use ($dosensToDelete) {
                foreach ($dosensToDelete as $dosen) {
                    try {
                        // Simpan user_id sebelum menghapus dosen
                        $userId = $dosen->user_id;

                        // Force delete dosen (bypass soft delete)
                        $dosenResult = $dosen->forceDelete();
                        Log::info('Hasil penghapusan dosen:', [
                            'dosen_id' => $dosen->id,
                            'berhasil' => $dosenResult
                        ]);

                        // Setelah dosen dihapus, cek apakah user memiliki dosen lain
                        // Termasuk yang soft deleted
                        $userHasOtherDosen = Dosen::withTrashed()
                            ->where('user_id', $userId)
                            ->exists();

                        if (!$userHasOtherDosen) {
                            // Hapus user jika tidak memiliki dosen lain
                            $user = User::find($userId);
                            if ($user) {
                                $userResult = $user->delete();
                                Log::info('Hasil penghapusan user:', [
                                    'user_id' => $userId,
                                    'berhasil' => $userResult
                                ]);
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
            throw $e;
        }
    }
}
