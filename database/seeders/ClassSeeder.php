<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Prodi;

class ClassSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $prodiId = '5a5e8c63-1234-4abc-89de-56789abcdef0'; // D3 Teknik Informatika (Bisa disesuaikan)

        DB::table('class_room')->insert([
            // Angkatan 2024 
            [
                'id' => 'be6ad5a-a8e7-4654-aa65-1a3301a394ab',
                'class_name' => 'A',
                'prodi_id' => $prodiId,
                'angkatan' => '2024',
            ],
            [
                'id' => 'be6ad5a-a8e7-4654-aa65-1a3301a394bc',
                'class_name' => 'B',
                'prodi_id' => $prodiId,
                'angkatan' => '2024',
            ],
            [
                'id' => 'be6ad5a-a8e7-4654-aa65-1a3301a394cd',
                'class_name' => 'C',
                'prodi_id' => $prodiId,
                'angkatan' => '2024',
            ],
        ]);
    }
}
