<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClassSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('class_room')->insert([
            [
                'id' => 'a7e6ad5a-a8e7-4654-aa65-1a3301a394cl',
                'class_name' => 'B',
                'prodi_id' =>'a0e6ad5a-a8e7-4654-aa65-1a3301a394cl',
                'angkatan' => '2022',
            ],
            [
                'id' => 'be6ad5a-a8e7-4654-aa65-1a3301a394cl',
                'class_name' => 'A',
                'prodi_id' =>'a0e6ad5a-a8e7-4654-aa65-1a3301a394cl',
                'angkatan' => '2022',
            ],
        ]);
    }
}
