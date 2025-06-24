<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DosenSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $dosens = [
            ['id' => 'ra483968-7f8c-4c01-af8a-936823bf6bda','nip' => '12345678 901234 5 001', 'name' => 'Dosen_1', 'email' => 'dosen1@proyek.com', 'kode_dosen' => 'DSN_1'],
            ['id' => 'us483968-7f8c-4c02-af8b-936823bf6bdi','nip' => '198903252019032023', 'name' => 'Sri Ratna Wulan, S.Pd., M.T.', 'email' => 'sw@proyek.com', 'kode_dosen' => 'KO076N'],
            ['id' => 'us483968-7f8c-4c03-af8c-936823bf6bdu','nip' => '199003022019032019', 'name' => 'Rahil Jumiyani, S.ST., M.Sc', 'email' => 'ra@proyek.com', 'kode_dosen' => 'KO062N'],
            ['id' => 'us483968-7f8c-4c04-af8d-936823bf6bde','nip' => '198906102019032019', 'name' => 'Asri Maspupah, S.S.T., M.T.', 'email' => 'am@proyek.com', 'kode_dosen' => 'KO067N'],
            ['id' => 'us483968-7f8c-4c05-af8e-936823bf6bdo','nip' => '19661018 199512 1 001', 'name' => 'Joe Lian Min, M.Eng.', 'email' => 'jo@proyek.com', 'kode_dosen' => 'KO007N'],
            ['id' => 'be483968-7f8c-4c05-af8e-936823bf6bdu','nip' => '199301062019031017', 'name' => 'Lukmannul Hakim Firdaus, S.Kom., M.T.', 'email' => 'lh@proyek.com', 'kode_dosen' => 'KO072N'],
            ['id' => 'rs483968-7f8c-4c05-af8e-936823bf6bdk','nip' => '19800419 200501 1 002', 'name' => 'Irwan Setiawan, S.Si., M.T.', 'email' => 'is@proyek.com', 'kode_dosen' => 'KO045N'],
        ];

        foreach ($dosens as $index => $dosen) {
            $user = DB::table('users')->where('email', $dosen['email'])->first(); // Cari berdasarkan email

            if ($user) {
                DB::table('dosen')->insert([
                    'id' => $dosen['id'],
                    'user_id' => $user->id,
                    'nip' => $dosen['nip'],
                    'kode_dosen' => $dosen['kode_dosen'], // Format DSN1, DSN2, dst.
                    'major_id' => '73597463-6c5f-4357-8733-e5f3edb26b44', // Teknik Komputer dan Informatika
                ]);
            }
        }
    }
}
