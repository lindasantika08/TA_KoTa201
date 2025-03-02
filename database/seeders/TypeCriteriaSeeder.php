<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TypeCriteriaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            [
                'id' => 'tc483968-7f8c-4c01-af8a-936823bf6btc',
                'aspect' => 'Pengumpulan Iklan Lowongan Kerja',
                'criteria' => 'Banyaknya iklan dan keragaman platform yang digunakan.',
                'bobot_1' => 'Mengumpulkan sedikit iklan, platform terbatas.',
                'bobot_2' => 'Mengumpulkan cukup iklan, platform terbatas.',
                'bobot_3' => 'Iklan cukup, platform yang digunakan terbatas.',
                'bobot_4' => 'Mengumpulkan banyak iklan, platform cukup beragam.',
                'bobot_5' => 'Mengumpulkan banyak iklan dari banyak platform.',
            ],
            [
                'id' => 'tc483968-7f8c-4c02-af8a-936823bf6btc',
                'aspect' => 'Pemahaman terhadap Isi Iklan',
                'criteria' => 'Pemahaman informasi penting dari iklan yang dikumpulkan.',
                'bobot_1' => 'Tidak memahami isi iklan.',
                'bobot_2' => 'Memahami sebagian kecil isi iklan.',
                'bobot_3' => 'Cukup memahami isi iklan.',
                'bobot_4' => 'Memahami sebagian besar isi iklan.',
                'bobot_5' => 'Memahami isi iklan dengan sangat baik.',
            ],
            [
                'id' => 'tc483968-7f8c-4c03-af8a-936823bf6btc',
                'aspect' => 'Pembuatan Struktur Data',
                'criteria' => 'Kesesuaian dan kejelasan struktur data untuk memuat informasi.',
                'bobot_1' => 'Struktur tidak sesuai dengan data.',
                'bobot_2' => 'Struktur kurang sesuai, masih perlu banyak perbaikan.',
                'bobot_3' => 'Struktur cukup sesuai, namun ada kekurangan.',
                'bobot_4' => 'Struktur hampir sesuai, hanya ada sedikit kekurangan.',
                'bobot_5' => 'Struktur data sangat baik dan lengkap.',
            ],
            [
                'id' => 'tc483968-7f8c-4c04-af8a-936823bf6btc',
                'aspect' => 'Penginputan Data ke Excel',
                'criteria' => 'Ketelitian dan keakuratan penginputan data.',
                'bobot_1' => 'Banyak kesalahan, tidak teliti.',
                'bobot_2' => 'Cukup banyak kesalahan, masih kurang teliti.',
                'bobot_3' => 'Cukup akurat, terdapat beberapa kesalahan.',
                'bobot_4' => 'Hanya ada sedikit kesalahan, cukup teliti.',
                'bobot_5' => 'Data diinput dengan akurat dan tanpa kesalahan.',
            ],
            [
                'id' => 'tc483968-7f8c-4c05-af8a-936823bf6btc',
                'aspect' => 'Penggabungan Data dengan Kelompok Lain',
                'criteria' => 'Partisipasi dalam menggabungkan data kelompok.',
                'bobot_1' => 'Tidak aktif dalam proses penggabungan.',
                'bobot_2' => 'Kurang aktif dalam penggabungan, kontribusi minim.',
                'bobot_3' => 'Cukup aktif namun kontribusi terbatas.',
                'bobot_4' => 'Aktif dalam penggabungan, namun ada beberapa kekurangan.',
                'bobot_5' => 'Sangat aktif dan membantu menyelesaikan masalah.',
            ],
            [
                'id' => 'tc483968-7f8c-4c06-af8a-936823bf6btc',
                'aspect' => 'Kolaborasi dan Kerjasama Tim',
                'criteria' => 'Kemampuan bekerja sama dengan anggota kelompok lain.',
                'bobot_1' => 'Tidak banyak terlibat dalam kerjasama.',
                'bobot_2' => 'Kurang terlibat dalam kerjasama, sering pasif.',
                'bobot_3' => 'Cukup terlibat dalam kerjasama.',
                'bobot_4' => 'Terlibat dengan baik, meskipun masih bisa ditingkatkan.',
                'bobot_5' => 'Sangat kooperatif dan membantu tim dengan baik.',
            ],
            [
                'id' => 'tc483968-7f8c-4c07-af8a-936823bf6btc',
                'aspect' => 'Komunikasi',
                'criteria' => 'Efektivitas dalam menyampaikan ide dan berdiskusi.',
                'bobot_1' => 'Komunikasi tidak jelas atau tidak efektif.',
                'bobot_2' => 'Komunikasi kurang jelas, sering terjadi kesalahpahaman.',
                'bobot_3' => 'Komunikasi cukup baik namun kurang terbuka.',
                'bobot_4' => 'Komunikasi baik, tetapi masih bisa lebih efektif.',
                'bobot_5' => 'Komunikasi sangat baik, ide tersampaikan jelas.',
            ],
            [
                'id' => 'tc483968-7f8c-4c08-af8a-936823bf6btc',
                'aspect' => 'Pemecahan Masalah',
                'criteria' => 'Kemampuan mengidentifikasi dan menyelesaikan masalah.',
                'bobot_1' => 'Tidak aktif dalam menyelesaikan masalah.',
                'bobot_2' => 'Kurang aktif dalam menemukan solusi.',
                'bobot_3' => 'Membantu dalam beberapa situasi.',
                'bobot_4' => 'Aktif dalam menyelesaikan masalah, namun kurang sistematis.',
                'bobot_5' => 'Sangat aktif menyelesaikan masalah dan proaktif.',
            ],
            [
                'id' => 'tc483968-7f8c-4c09-af8a-936823bf6btc',
                'aspect' => 'Manajemen Waktu',
                'criteria' => 'Kemampuan menyelesaikan tugas sesuai tenggat waktu.',
                'bobot_1' => 'Banyak tugas yang terlambat diselesaikan.',
                'bobot_2' => 'Beberapa tugas terlambat, sering menunda pekerjaan.',
                'bobot_3' => 'Beberapa tugas terlambat namun masih dapat dikejar.',
                'bobot_4' => 'Sebagian besar tugas selesai tepat waktu, hanya ada sedikit keterlambatan.',
                'bobot_5' => 'Semua tugas diselesaikan tepat waktu.',
            ],
        ];

        DB::table('type_criteria')->insert($data);
    }
}
