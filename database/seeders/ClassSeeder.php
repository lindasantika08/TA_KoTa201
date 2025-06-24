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
        $prodiId = '6a5e8c64-1234-4xyt-79de-56789abcdef9';
        $prodiIdD3 = '5a5e8c63-1234-4abc-89de-56789abcdef0';

        DB::table('class_room')->insert([
            // Angkatan 2022
            [
                'id' => 'be6ad5a-a8e7-4654-aa65-1a3301a394ab',
                'class_name' => 'A',
                'prodi_id' => $prodiId,
                'angkatan' => '2022',
            ],
            [
                'id' => 'be6ad5a-a8e7-4654-aa65-1a3301a394bc',
                'class_name' => 'B',
                'prodi_id' => $prodiId,
                'angkatan' => '2022',
            ],
            [
                'id' => 'be6ad5a-a8e7-4654-aa65-1a3301a394abd',
                'class_name' => 'A',
                'prodi_id' => $prodiIdD3,
                'angkatan' => '2024',
            ],
            [
                'id' => 'be6ad5a-a8e7-4654-aa65-1a3301a394abe',
                'class_name' => 'B',
                'prodi_id' => $prodiIdD3,
                'angkatan' => '2024',
            ],
            [
                'id' => 'be6ad5a-a8e7-4654-aa65-1a3301a394abf',
                'class_name' => 'C',
                'prodi_id' => $prodiIdD3,
                'angkatan' => '2024',
            ],
        ]);
    }
}
