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
                'nip' => '123456789012345678',
                'nim' => null,
                'kode_dosen' => 'LS',
                'role' => 'dosen',
                'angkatan' => null,
                'class' => null,
                'prodi' => null,
                'jurusan' => 'Teknik Komputer dan Informatika',
                'remember_token' => null,
                'photo' => null,
            ],
            [
                'id' => 'ea483968-7f8c-4c02-af8a-936823bf6bcx',
                'name' => 'Reval',
                'email' => 'reval@proyek.com',
                'password' => bcrypt("qwerty123"),
                'nip' => '123456789012348678',
                'nim' => null,
                'kode_dosen' => 'RS',
                'role' => 'dosen',
                'angkatan' => null,
                'class' => null,
                'prodi' => null,
                'jurusan' => 'Teknik Komputer dan Informatika',
                'remember_token' => null, // jika perlu
                'photo' => null, // jika perlu
            ],
            [
                'id' => 'ea483968-7f8c-4c02-af8a-936823bf6bcl',
                'name' => 'Arsy',
                'email' => 'arsy@proyek.com',
                'password' => bcrypt("qwerty123"),
                'nip' => '127456789012345678',
                'nim' => null,
                'kode_dosen' => 'NA',
                'role' => 'dosen',
                'angkatan' => null,
                'class' => null,
                'prodi' => null,
                'jurusan' => 'Teknik Komputer dan Informatika',
                'remember_token' => null, // jika perlu
                'photo' => null, // jika perlu
            ],
            [
                'id' => 'ra483968-7f8c-4c02-af8a-936823bf6bcd',
                'name' => 'Thoriq',
                'email' => 'mahasiswa@proyek.com',
                'password' => bcrypt("qwerty123"),
                'nip' => null,
                'nim' => '123456789',
                'role' => 'mahasiswa',
                'kode_dosen' => null,
                'angkatan' => '2021',
                'class' => 'A',
                'prodi' => 'D-3 Teknik Informatika',
                'jurusan' => 'Teknik Komputer dan Informatika',
                'remember_token' => null,
                'photo' => null,
            ],
            [
                'id' => 'ra483968-7f8c-4c02-af8a-936823bf6bce',
                'name' => 'Danen',
                'email' => 'danen@proyek.com',
                'password' => bcrypt("qwerty123"),
                'nip' => null,
                'nim' => '231511046',
                'role' => 'mahasiswa',
                'kode_dosen' => null,
                'angkatan' => '2021',
                'class' => 'A',
                'prodi' => 'D-3 Teknik Informatika',
                'jurusan' => 'Teknik Komputer dan Informatika',
                'remember_token' => null, // jika perlu
                'photo' => null, // jika perlu
            ],
            [
                'id' => 'ra483968-7f8c-4c02-af8a-936823bf6bcf',
                'name' => 'Adhiya',
                'email' => 'adhiya@proyek.com',
                'password' => bcrypt("qwerty123"),
                'nip' => null,
                'nim' => '231511034',
                'role' => 'mahasiswa',
                'kode_dosen' => null,
                'angkatan' => '2021',
                'class' => 'A',
                'prodi' => 'D-3 Teknik Informatika',
                'jurusan' => 'Teknik Komputer dan Informatika',
                'remember_token' => null, // jika perlu
                'photo' => null, // jika perlu
            ],
            [
                'id' => 'ra483968-7f8c-4c02-af8a-936823bf6bcg',
                'name' => 'Shaka',
                'email' => 'shaka@proyek.com',
                'password' => bcrypt("qwerty123"),
                'nip' => null,
                'nim' => '241511099',
                'role' => 'mahasiswa',
                'kode_dosen' => null,
                'angkatan' => '2021',
                'class' => 'A',
                'prodi' => 'D-3 Teknik Informatika',
                'jurusan' => 'Teknik Komputer dan Informatika',
                'remember_token' => null, // jika perlu
                'photo' => null, // jika perlu
            ],
            [
                'id' => 'ra483968-7f8c-4c02-af8a-936825bf6bcg',
                'name' => 'Adinda',
                'email' => 'adinda@proyek.com',
                'password' => bcrypt("qwerty123"),
                'nip' => null,
                'nim' => '231511035',
                'role' => 'mahasiswa',
                'kode_dosen' => null,
                'angkatan' => '2021',
                'class' => 'A',
                'prodi' => 'D-3 Teknik Informatika',
                'jurusan' => 'Teknik Komputer dan Informatika',
                'remember_token' => null, // jika perlu
                'photo' => null, // jika perlu
            ],
            [
                'id' => 'ra483968-7f8n-4c02-af8b-936823bf6bcg',
                'name' => 'Alya',
                'email' => 'alya@proyek.com',
                'password' => bcrypt("qwerty123"),
                'nip' => null,
                'nim' => '231511055',
                'role' => 'mahasiswa',
                'kode_dosen' => null,
                'angkatan' => '2021',
                'class' => 'A',
                'prodi' => 'D-3 Teknik Informatika',
                'jurusan' => 'Teknik Komputer dan Informatika',
                'remember_token' => null, // jika perlu
                'photo' => null, // jika perlu
            ],
            [
                'id' => 'ra483968-7f8c-4c02-af8a-936823cf6bcg',
                'name' => 'Fadel',
                'email' => 'fadel@proyek.com',
                'password' => bcrypt("qwerty123"),
                'nip' => null,
                'nim' => '231511060',
                'role' => 'mahasiswa',
                'kode_dosen' => null,
                'angkatan' => '2021',
                'class' => 'A',
                'prodi' => 'D-3 Teknik Informatika',
                'jurusan' => 'Teknik Komputer dan Informatika',
                'remember_token' => null, // jika perlu
                'photo' => null, // jika perlu
            ],
            [
                'id' => 'ra483968-7f8c-4c02-af8a-936823bf3bcg',
                'name' => 'Barry',
                'email' => 'barry@proyek.com',
                'password' => bcrypt("qwerty123"),
                'nip' => null,
                'nim' => '231511033',
                'role' => 'mahasiswa',
                'kode_dosen' => null,
                'angkatan' => '2021',
                'class' => 'A',
                'prodi' => 'D-3 Teknik Informatika',
                'jurusan' => 'Teknik Komputer dan Informatika',
                'remember_token' => null, // jika perlu
                'photo' => null, // jika perlu
            ],
           
        ]);
    }
}
