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
                'batch_year' => '2022/2023',
                'angkatan' => '2022',
                'project_id' => '9e3a1a20-488d-4c66-af57-a97d31f1a4df',
                'mahasiswa_id' => 'd7e6ad5a-a8e7-4654-aa65-1a3301a394so', // Adhiya
                'group' => 'kelompok 1',
                'dosen_id' => 'd7e6ad5a-a8e7-4654-aa65-1a3301a394ce'
            ],
            [
                'id' => Str::uuid()->toString(),
                'batch_year' => '2022/2023',
                'angkatan' => '2022',
                'project_id' => '9e3a1a20-488d-4c66-af57-a97d31f1a4df',
                'mahasiswa_id' => 'd7e6ad5a-a8e7-4654-aa65-1a3301a394cs', // Danendra
                'group' => 'kelompok 1',
                'dosen_id' => 'd7e6ad5a-a8e7-4654-aa65-1a3301a394ce'
            ],
            [
                'id' => Str::uuid()->toString(),
                'batch_year' => '2022/2023',
                'angkatan' => '2022',
                'project_id' => '9e3a1a20-488d-4c66-af57-a97d31f1a4df',
                'mahasiswa_id' => 'd7e6ad5a-a8e7-4654-aa65-1a3301a394ln', // Linda
                'group' => 'kelompok 1',
                'dosen_id' => 'd7e6ad5a-a8e7-4654-aa65-1a3301a394ce'
            ],
        ];

        DB::table('groups')->insert($groups);
    }
}
