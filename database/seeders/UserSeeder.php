<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facade\Hash;
use Illuminate\Support\Str;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('users')->insert([
            [
                'id' =>'ea483968-7f8c-4c02-af8a-936823bf6bcu',
                'name' => 'Linda',
                'email' => 'linda.santika.tif22@polban.ac.id',
                'password' => bcrypt("qwerty123"),
                'nip' => '12345678901234567',
                'role' => 'dosen',
            ],
            [
                'id' => 'ra483968-7f8c-4c02-af8a-936823bf6bcd',
                'name' => 'Thoriq',
                'email' => 'mahasiswa@proyek.com',
                'password' => bcrypt("qwerty123"),
                'nim'=> '123456789',
                'role' => 'mahasiswa',
            ],
        ]);
    }
}
