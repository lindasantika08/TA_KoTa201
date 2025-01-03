<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facade\Hash;
use Illuminate\Support\Str;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('roles')->insert([
            [
                'id' => '07e6ad5a-a8e7-4654-aa65-1a3301a394cl',
                'role' => 'Mahasiswa',
                'guard_name' =>'sanctum',
            ],
            [
                'id' => 'ea483968-7f8c-4c02-af8a-936823bf6bc0',
                'role' => 'Dosen',
                'guard_name' =>'sanctum',
            ],
        ]);
    }
}
