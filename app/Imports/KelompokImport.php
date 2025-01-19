<?php

namespace App\Imports;

use App\Models\Kelompok;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class KelompokImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        // Log data baris untuk debug
        Log::info('Importing row:', $row);

        // Mencari mahasiswa berdasarkan nim dan role 'mahasiswa'
        $mahasiswa = User::where('nim', $row['nim'])
            ->where('role', 'mahasiswa')
            ->first();

        if (!$mahasiswa) {
            Log::warning('Mahasiswa not found for NIM:', ['nim' => $row['nim']]);
            return null; // Abaikan baris ini jika mahasiswa tidak ditemukan
        }

        // Memecah kolom dosen_manager untuk mendapatkan kode_dosen
        $dosenManager = explode(' - ', $row['dosen_manager']); // Format: "Nama - Kode"
        $kodeDosen = count($dosenManager) > 1 ? trim($dosenManager[1]) : null;

        // Mencari dosen berdasarkan kode_dosen dan role 'dosen'
        $dosen = $kodeDosen
            ? User::where('kode_dosen', $kodeDosen)
                ->where('role', 'dosen')
                ->first()
            : null;

        if (!$dosen) {
            Log::warning('Dosen not found for kode_dosen:', ['kode_dosen' => $kodeDosen]);
        }

        // Membuat atau memperbarui data di tabel kelompok
        $kelompok = Kelompok::updateOrCreate(
            [
                'user_id' => $mahasiswa->id,
                'kelompok' => $row['kelompok'],
            ],
            [
                'id' => Str::uuid(),
                'tahun_ajaran' => $row['tahun_ajaran'],
                'nama_proyek' => $row['proyek'],
                'dosen_id' => $dosen ? $dosen->id : null,
            ]
        );

        Log::info('Kelompok processed:', ['kelompok_id' => $kelompok->id]);

        return $kelompok;
    }
}
