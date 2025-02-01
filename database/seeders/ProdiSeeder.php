<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProdiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('prodi')->insert([
            [
                'id' => 'a0e6ad5a-a8e7-4654-aa65-1a3301a394cl',
                'major_id' => '73597463-6c5f-4357-8733-e5f3edb26b44',
                'prodi_name' =>'D3',
            ],
            [
                'id' => 'bye6ad5a-a8e7-4654-aa65-1a3301a394cl',
                'major_id' => '73597463-6c5f-4357-8733-e5f3edb26b44',
                'prodi_name' =>'D4',
            ],
        ]);
    }
}
