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
            ['id' => 'ra483968-7f8c-4c01-af8a-936823bf6bda','nip' => '12345678 901234 5 001', 'name' => 'Dosen_1', 'email' => 'dosen1@proyek.com'],
            ['id' => 'ra483968-7f8c-4c02-af8b-936823bf6bdi','nip' => '12345678 901234 5 002', 'name' => 'Dosen_2', 'email' => 'dosen2@proyek.com'],
            ['id' => 'ra483968-7f8c-4c03-af8c-936823bf6bdu','nip' => '12345678 901234 5 003', 'name' => 'Dosen_3', 'email' => 'dosen3@proyek.com'],
            ['id' => 'ra483968-7f8c-4c04-af8d-936823bf6bde','nip' => '12345678 901234 5 004', 'name' => 'Dosen_4', 'email' => 'dosen4@proyek.com'],
            ['id' => 'ra483968-7f8c-4c05-af8e-936823bf6bdo','nip' => '12345678 901234 5 005', 'name' => 'Dosen_5', 'email' => 'dosen5@proyek.com'],
        ];

        foreach ($dosens as $index => $dosen) {
            $user = DB::table('users')->where('email', $dosen['email'])->first(); // Cari berdasarkan email

            if ($user) {
                DB::table('dosen')->insert([
                    'id' => $dosen['id'],
                    'user_id' => $user->id,
                    'nip' => $dosen['nip'],
                    'kode_dosen' => 'DSN' . ($index + 1), // Format DSN1, DSN2, dst.
                    'major_id' => '73597463-6c5f-4357-8733-e5f3edb26b44', // Teknik Komputer dan Informatika
                ]);
            }
        }
    }
}
