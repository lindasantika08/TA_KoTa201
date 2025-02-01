<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DosenSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('dosen')->insert([
            [
                'id' => 'd7e6ad5a-a8e7-4654-aa65-1a3301a394ce',
                'user_id' => 'ea483968-7f8c-4c02-af8a-936823bf6bcu',
                'nip' =>'12345678901234566',
                'kode_dosen' => 'TS',
                'major_id' => '73597463-6c5f-4357-8733-e5f3edb26b44',
            ],
            [
                'id' => 'k7e6ad5a-a8e7-4654-aa65-1a3301a394cg',
                'user_id' => 'ea483968-7f8c-4c02-af8a-936823bf6bcx',
                'nip' =>'12345678901234560',
                'kode_dosen' => 'PL',
                'major_id' => '19b2c063-715a-421e-84f9-8b27257a101c',
            ],
        ]);
    }
}
