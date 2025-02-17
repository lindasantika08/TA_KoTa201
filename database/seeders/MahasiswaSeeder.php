<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MahasiswaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('mahasiswa')->insert([
            [
                'id' => 'd7e6ad5a-a8e7-4654-aa65-1a3301a394so',
                'user_id' => 'ra483968-7f8c-4c02-af8a-936823bf6bcd',
                'class_id' =>'a7e6ad5a-a8e7-4654-aa65-1a3301a394cl',
                'nim' => '221511034',
            ],
            [
                'id' => 'd7e6ad5a-a8e7-4654-aa65-1a3301a394cs',
                'user_id' => 'ra483968-7f8c-4c03-af8a-936823bf6bcd',
                'class_id' =>'a7e6ad5a-a8e7-4654-aa65-1a3301a394cl',
                'nim' => '221511046',
            ],
            [
                'id' => 'd7e6ad5a-a8e7-4654-aa65-1a3301a394ln',
                'user_id' => 'ra483968-7f8c-4c04-af8a-936823bf6bcd',
                'class_id' =>'a7e6ad5a-a8e7-4654-aa65-1a3301a394cl',
                'nim' => '221511053',
            ],
            [
                'id' => 'k7e6ad5a-a8e7-4654-aa65-1a3301a394cu',
                'user_id' => 'ra483968-7f8c-4c02-af8a-936823bf6bcg',
                'class_id' =>'a7e6ad5a-a8e7-4654-aa65-1a3301a394cl',
                'nim' => '221511057',
            ],
        ]);
    }
}
