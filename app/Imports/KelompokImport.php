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
        Log::info('Importing row:', $row);

        // Mencari mahasiswa berdasarkan nim dan role 'mahasiswa'
        $mahasiswa = User::where('nim', $row['nim'])
            ->where('role', 'mahasiswa')
            ->first();

        if (!$mahasiswa) {
            Log::warning('Mahasiswa not found for NIM:', ['nim' => $row['nim']]);
            return null;
        }

        // Memecah kolom dosen_manager untuk mendapatkan kode_dosen
        $dosenManager = explode(' - ', $row['dosen_manajer']);
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

        // Data baru yang sedang diimpor
        $tahunAjaran = $row['tahun_ajaran'];
        $namaProyek = $row['proyek'];
        $kelompokBaru = [
            'user_id' => $mahasiswa->id,
            'kelompok' => $row['kelompok'],
            'tahun_ajaran' => $tahunAjaran,
            'nama_proyek' => $namaProyek,
        ];

        // Tambahkan data baru ke koleksi (untuk nanti dibandingkan dengan data lama)
        static $dataBaru = []; // Digunakan untuk menyimpan semua data baru selama proses impor
        $dataBaru[] = $kelompokBaru;

        // Simpan atau perbarui data baru
        Kelompok::updateOrCreate(
            [
                'tahun_ajaran' => $tahunAjaran,
                'nama_proyek' => $namaProyek,
                'user_id' => $mahasiswa->id,
            ],
            [
                'id' => Str::uuid(),
                'kelompok' => $row['kelompok'],
                'dosen_id' => $dosen ? $dosen->id : null,
            ]
        );

        // Hapus data lama yang tidak ada di data baru (hanya sekali setelah seluruh impor selesai)
        if (end($dataBaru) === $kelompokBaru) { // Memastikan ini adalah baris terakhir
            $dataLama = Kelompok::where('tahun_ajaran', $tahunAjaran)
                ->where('nama_proyek', $namaProyek)
                ->get();

            foreach ($dataLama as $data) {
                $isFound = collect($dataBaru)->contains(function ($item) use ($data) {
                    return $item['user_id'] == $data->user_id &&
                        $item['kelompok'] == $data->kelompok;
                });

                if (!$isFound) {
                    $data->delete();
                    Log::info('Deleted old kelompok:', [
                        'user_id' => $data->user_id,
                        'kelompok' => $data->kelompok,
                    ]);
                }
            }

            Log::info('Cleanup complete for tahun_ajaran and proyek:', [
                'tahun_ajaran' => $tahunAjaran,
                'nama_proyek' => $namaProyek,
            ]);
        }

        return null;
    }
}
