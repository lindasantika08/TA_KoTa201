<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        
        $groups = [
            [
                'id' => Str::uuid()->toString(),
                'batch_year' => '2024/2025',
                'angkatan' => '2024',
                'project_id' => 'oe4ad5a-a0e7-4654-qq45-1a3301a394pj',
                'mahasiswa_id' => 'aa483968-7f8c-1c05-af8e-936823bf1bda', // Irvan Supriadi Situmorang
                'group' => 'kelompok 1',
                'dosen_id' => 'ra483968-7f8c-4c01-af8a-936823bf6bda'
            ],
            [
                'id' => Str::uuid()->toString(),
                'batch_year' => '2024/2025',
                'angkatan' => '2024',
                'project_id' => 'oe4ad5a-a0e7-4654-qq45-1a3301a394pj',
                'mahasiswa_id' => 'aa483968-7f8c-1c05-af8e-936823bf7bdp', // Maulana Ishak
                'group' => 'Kelompok 1',
                'dosen_id' => 'ra483968-7f8c-4c01-af8a-936823bf6bda' // DSN1
            ],
            [
                'id' => Str::uuid()->toString(),
                'batch_year' => '2024/2025',
                'angkatan' => '2024',
                'project_id' => 'oe4ad5a-a0e7-4654-qq45-1a3301a394pj',
                'mahasiswa_id' => 'aa483968-7f8c-1c05-af8e-936823bf7bdy', // Raffi Fauzi Hermawan
                'group' => 'Kelompok 1',
                'dosen_id' => 'ra483968-7f8c-4c01-af8a-936823bf6bda' // DSN1
            ],
            [
                'id' => Str::uuid()->toString(),
                'batch_year' => '2024/2025',
                'angkatan' => '2024',
                'project_id' => 'oe4ad5a-a0e7-4654-qq45-1a3301a394pj',
                'mahasiswa_id' => 'aa483968-7f8c-8c05-af8e-936823bf9bda', // Tamam Hisabulah
                'group' => 'Kelompok 1',
                'dosen_id' => 'ra483968-7f8c-4c01-af8a-936823bf6bda' // DSN1
            ],
            [
                'id' => Str::uuid()->toString(),
                'batch_year' => '2024/2025',
                'angkatan' => '2024',
                'project_id' => 'oe4ad5a-a0e7-4654-qq45-1a3301a394pj',
                'mahasiswa_id' => 'aa483968-7f8c-6c05-af8e-936823bf1bdc', // Wafi Ahlam Rizqulloh
                'group' => 'Kelompok 1',
                'dosen_id' => 'ra483968-7f8c-4c01-af8a-936823bf6bda' // DSN1
            ],
        ];

        DB::table('groups')->insert($groups);
    }
}
