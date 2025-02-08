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
                'name' => 'Linda',
                'email' => 'dosen@proyek.com',
                'password' => bcrypt("qwerty123"),
                'role' => 'dosen',
                'remember_token' => null,

            ],
            [
                'id' => 'ea483968-7f8c-4c02-af8a-936823bf6bcx',
                'name' => 'Reval',
                'email' => 'reval@proyek.com',
                'password' => bcrypt("qwerty123"),
                'role' => 'dosen',
                'remember_token' => null,

            ],
            [
                'id' => 'ea483968-7f8c-4c02-af8a-936823bf6bcl',
                'name' => 'Arsy',
                'email' => 'arsy@proyek.com',
                'password' => bcrypt("qwerty123"),
                'role' => 'dosen',
                'remember_token' => null,

            ],
            [
                'id' => 'ra483968-7f8c-4c02-af8a-936823bf6bcd',
                'name' => 'Thoriq',
                'email' => 'mahasiswa@proyek.com',
                'password' => bcrypt("qwerty123"),
                'role' => 'mahasiswa',
                'remember_token' => null,

            ],
            [
                'id' => 'ra483968-7f8c-4c02-af8a-936823bf6bce',
                'name' => 'Danen',
                'email' => 'danen@proyek.com',
                'password' => bcrypt("qwerty123"),
                'role' => 'mahasiswa',
                'remember_token' => null,

            ],
            [
                'id' => 'ra483968-7f8c-4c02-af8a-936823bf6bcf',
                'name' => 'Adhiya',
                'email' => 'adhiya@proyek.com',
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
                'id' => 'ra483968-7f8c-4c02-af8a-936825bf6bcg',
                'name' => 'Adinda',
                'email' => 'adinda@proyek.com',
                'password' => bcrypt("qwerty123"),
                'role' => 'mahasiswa',
                'remember_token' => null,

            ],
            [
                'id' => 'ra483968-7f8n-4c02-af8b-936823bf6bcg',
                'name' => 'Alya',
                'email' => 'alya@proyek.com',
                'password' => bcrypt("qwerty123"),
                'role' => 'mahasiswa',
                'remember_token' => null,

            ],
            [
                'id' => 'ra483968-7f8c-4c02-af8a-936823cf6bcg',
                'name' => 'Fadel',
                'email' => 'fadel@proyek.com',
                'password' => bcrypt("qwerty123"),
                'role' => 'mahasiswa',
                'remember_token' => null,

            ],
            [
                'id' => 'ra483968-7f8c-4c02-af8a-936823bf3bcg',
                'name' => 'Barry',
                'email' => 'barry@proyek.com',
                'password' => bcrypt("qwerty123"),
                'role' => 'mahasiswa',
                'remember_token' => null,

            ],

        ]);
    }
}
