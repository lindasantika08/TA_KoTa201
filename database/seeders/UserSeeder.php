<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('users')->insert([
            [
                'id' => 'ea483968-7f8c-4c02-af8a-936823bf6bcu',
                'name' => 'Dosen_1',
                'email' => 'dosen@proyek.com',
                'password' => bcrypt("qwerty123"),
                'role' => 'dosen',
                'remember_token' => null,

            ],
            [
                'id' => 'ea483968-7f8c-4c02-af8a-936823bf6bcx',
                'name' => 'Dosen_2',
                'email' => 'dosen2@proyek.com',
                'password' => bcrypt("qwerty123"),
                'role' => 'dosen',
                'remember_token' => null,

            ],
            [
                'id' => 'ra483968-7f8c-4c02-af8a-936823bf6bcd',
                'name' => 'Adhiya Rahma Anzani',
                'email' => 'mahasiswa@proyek.com',
                'password' => bcrypt("qwerty123"),
                'role' => 'mahasiswa',
                'remember_token' => null,

            ],
            [
                'id' => 'ra483968-7f8c-4c03-af8a-936823bf6bcd',
                'name' => 'Danendra Gafrila',
                'email' => 'mahasiswa1@proyek.com',
                'password' => bcrypt("qwerty123"),
                'role' => 'mahasiswa',
                'remember_token' => null,

            ],
            [
                'id' => 'ra483968-7f8c-4c04-af8a-936823bf6bcd',
                'name' => 'Linda Santika',
                'email' => 'mahasiswa2@proyek.com',
                'password' => bcrypt("qwerty123"),
                'role' => 'mahasiswa',
                'remember_token' => null,

            ],
            [
                'id' => 'ra483968-7f8c-4c02-af8a-936823bf6bcg',
                'name' => 'Shaka',
                'email' => 'shaka@proyek.com',
                'password' => bcrypt("qwerty123"),
                'role' => 'mahasiswa',
                'remember_token' => null,

            ],
            [
                'id' => 'ra483968-7f8c-4c02-af8a-936823bf6bcz',
                'name' => 'admin',
                'email' => 'admin@proyek.com',
                'password' => bcrypt("qwerty123"),
                'role' => 'admin',
                'remember_token' => null,

            ],

        ]);
    }
}
