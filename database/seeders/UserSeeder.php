<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('users')->insert([
            // ADMIN
            [
                'id' => 'ra483968-7f8c-4c02-af8a-936823bf6bcz',
                'name' => 'admin',
                'email' => 'admin@proyek.com',
                'password' => bcrypt("qwerty123"),
                'role' => 'admin',
                'remember_token' => null,

            ],
        ]);

        // DOSEN
        $dosens = [
            ['id' => 'us483968-7f8c-4c01-af8a-936823bf6bda','nip' => '12345678 901234 5 001', 'name' => 'Dosen_1', 'email' => 'dosen1@proyek.com'],
            ['id' => 'us483968-7f8c-4c02-af8b-936823bf6bdi','nip' => '198903252019032023', 'name' => 'Sri Ratna Wulan, S.Pd., M.T.', 'email' => 'sw@proyek.com'],
            ['id' => 'us483968-7f8c-4c03-af8c-936823bf6bdu','nip' => '199003022019032019', 'name' => 'Rahil Jumiyani, S.ST., M.Sc', 'email' => 'ra@proyek.com'],
            ['id' => 'us483968-7f8c-4c04-af8d-936823bf6bde','nip' => '198906102019032019', 'name' => 'Asri Maspupah, S.S.T., M.T.', 'email' => 'am@proyek.com'],
            ['id' => 'us483968-7f8c-4c05-af8e-936823bf6bdo','nip' => '19661018 199512 1 001', 'name' => 'Joe Lian Min, M.Eng.', 'email' => 'jo@proyek.com'],
            ['id' => 'be483968-7f8c-4c05-af8e-936823bf6bdu','nip' => '199301062019031017', 'name' => 'Lukmannul Hakim Firdaus, S.Kom., M.T.', 'email' => 'lh@proyek.com'],
            ['id' => 'rs483968-7f8c-4c05-af8e-936823bf6bdk','nip' => '19800419 200501 1 002', 'name' => 'Irwan Setiawan, S.Si., M.T.', 'email' => 'is@proyek.com'],
        ];
        foreach ($dosens as $dosen) {
            DB::table('users')->insert([
                'id' => $dosen['id'],
                'name' => $dosen['name'],
                'email' => $dosen['email'],
                'password' => bcrypt("qwerty123"),
                'role' => 'dosen',
                'remember_token' => null,
            ]);
        }

        $students = [
    //         // D4-A
            ['id' => 'sa483968-7f8c-1c05-af8e-936823bf1bda','nim' => '221524001', 'name' => 'Adinda Fauzia Puspita'],
            ['id' => 'sa483968-7f8c-1c05-af8e-936823bf2bdb','nim' => '221524002', 'name' => 'Akmal Goniyyu Hartono'],
            ['id' => 'sa483968-7f8c-1c05-af8e-936823bf3bdc','nim' => '221524003', 'name' => 'Aryo Rakatama'],
            ['id' => 'sa483968-7f8c-2c05-af8e-936823bf4bdd','nim' => '221524004', 'name' => 'Dafa Alfarizki Pratama'],
            ['id' => 'sa483968-7f8c-3c05-af8e-936823bf6bdf','nim' => '221524006', 'name' => 'Dean Adrian Baihaqi'],
            ['id' => 'sa483968-7f8c-5c05-af8e-936823bf8bdh','nim' => '221524008', 'name' => 'Farhan Muhammad Yasin'],
            ['id' => 'sa483968-7f8c-6c05-af8e-936823bf9bdi','nim' => '221524009', 'name' => 'Farrel Keiza Muhammad Yamin Putra'],
            ['id' => 'sa483968-7f8c-7c05-af8e-936823bf1bdj','nim' => '221524010', 'name' => 'Farrel Rahandika'],
            ['id' => 'sa483968-7f8c-6c05-af8e-936823bf2bdk','nim' => '221524011', 'name' => 'Hasna Fitriyani Khairunissa'],
            ['id' => 'sa483968-7f8c-7c05-af8e-936823bf3bdl','nim' => '221524012', 'name' => 'Muhamad Fahrizal Al Zaelani'],
            ['id' => 'sa483968-7f8c-8c05-af8e-936823bf4bdm','nim' => '221524013', 'name' => 'Muhamad Firman Firdaus'],
            ['id' => 'sa483968-7f8c-9c05-af8e-936823bf5bdn','nim' => '221524014', 'name' => 'Muhamad Mathar Rizqi'],
            ['id' => 'sa483968-7f8c-0c05-af8e-936823bf6bdo','nim' => '221524015', 'name' => 'Muhamad Rendi Zul Fauzi'],
            ['id' => 'sa483968-7f8c-1c05-af8e-936823bf7bdp','nim' => '221524016', 'name' => 'Muhammad Alvin Abdul Rozak Kusuma Putra'],
            ['id' => 'sa483968-7f8c-2c05-af8e-936823bf8bdq','nim' => '221524017', 'name' => 'Muhammad Alvyn Adhianto'],
            ['id' => 'sa483968-7f8c-3c05-af8e-936823bf9bdr','nim' => '221524018', 'name' => 'Muhammad Azharuddin Hamid'],
            ['id' => 'sa483968-7f8c-4c05-af8e-936823bf1bds','nim' => '221524019', 'name' => 'Muhammad Fikri Nur Sya bani'],
            ['id' => 'sa483968-7f8c-5c05-af8e-936823bf2bdt','nim' => '221524020', 'name' => 'Muhammad Raihan Fasya Mian'],
            ['id' => 'sa483968-7f8c-6c05-af8e-936823bf3bdu','nim' => '221524021', 'name' => 'Muhammad Rama Nurimani'],
            ['id' => 'sa483968-7f8c-7c05-af8e-936823bf4bdv','nim' => '221524022', 'name' => 'Mutiara Dwi Salma'],
            ['id' => 'sa483968-7f8c-8c05-af8e-936823bf5bdw','nim' => '221524023', 'name' => 'Naffa Lenteranisa'],
            ['id' => 'sa483968-7f8c-9c05-af8e-936823bf6bdx','nim' => '221524024', 'name' => 'Naila Saniyah Nur aini'],
            ['id' => 'sa483968-7f8c-9c05-af8e-936823bf8bdz','nim' => '221524026', 'name' => 'Rahma Divina'],
            ['id' => 'sa483968-7f8c-8c05-af8e-936823bf9bda','nim' => '221524027', 'name' => 'Rayhan Fanez Fathiadi'],
            ['id' => 'sa483968-7f8c-7c05-af8e-936823bf0bdb','nim' => '221524028', 'name' => 'Retryanzani Dwi Fauzan'],
            ['id' => 'sa483968-7f8c-6c05-af8e-936823bf1bdc','nim' => '221524029', 'name' => 'Rohiid Naufal Juliardi'],
            ['id' => 'sa483968-7f8c-5c05-af8e-936823bf2bdd','nim' => '221524030', 'name' => 'Roy Aziz Barera'],
            ['id' => 'sa483968-7f8c-4c05-af8e-936823bf3bde','nim' => '221524031', 'name' => 'Saabiq Muhyiyuddin Aulawi'],
            ['id' => 'sa483968-7f8c-3c05-af8e-936823bf4bdf','nim' => '221524032', 'name' => 'Welsya Almaira Indra'],
    //         // D4-B
            ['id' => 'bs483969-1f8c-4c01-af8e-936823bf6bdb','nim' => '221524033', 'name' => 'Alisha Nara Chandrakirana'],
            ['id' => 'bs483968-1f8c-4c02-bf8e-936823bf6bdb','nim' => '221524034', 'name' => 'Arnanda Prasatya'],
            ['id' => 'bs483967-1f8c-4c03-cf8e-936823bf6bdb','nim' => '221524035', 'name' => 'Asri Husnul Rosadi'],
            ['id' => 'bs483966-1f8c-4c04-df8e-936823bf6bdb','nim' => '221524036', 'name' => 'Banteng Harisantoso'],
            ['id' => 'bs483965-1f8c-4c05-ef8e-936823bf6bdb','nim' => '221524037', 'name' => 'Bhisma Chandra Yudha Setiawan'],
            ['id' => 'bs483963-1f8c-4c07-gf8e-936823bf6bdb','nim' => '221524039', 'name' => 'Farhan Muhammad Luthfi'],
            ['id' => 'bs483962-1f8c-4c08-hf8e-936823bf6bdb','nim' => '221524040', 'name' => 'Faris Abulkhoir'],
            ['id' => 'bs483961-1f8c-4c09-if8e-936823bf6bdb','nim' => '221524041', 'name' => 'Ferdi Ahmad Ariesta'],
            ['id' => 'bs483960-1f8c-4c00-jf8e-936823bf6bdb','nim' => '221524042', 'name' => 'Jeihan Ilham Kusumawardhana'],
            ['id' => 'bs483969-1f8c-4c01-kf8e-936823bf6bdb','nim' => '221524043', 'name' => 'Keanu Rayhan Harits'],
            ['id' => 'ts483978-1f8c-4c01-kf8e-936823bf6bdb','nim' => '221524044', 'name' => 'Mahardika Pratama'],
            ['id' => 'bs483968-1f8c-4c02-lf8e-936823bf6bdb','nim' => '221524045', 'name' => 'Mochamad Fathur Rabbani'],
            ['id' => 'bs483967-1f8c-4c03-mf8e-936823bf6bdb','nim' => '221524046', 'name' => 'Muhamad Agim'],
            ['id' => 'bs483965-1f8c-4c05-of8e-936823bf6bdb','nim' => '221524047', 'name' => 'Muhamad Fahri Yuwan Dwi Putra'],
            ['id' => 'bs483964-1f8c-4c06-pf8e-936823bf6bdb','nim' => '221524049', 'name' => 'Muhammad Daffa'],
            ['id' => 'bs483963-1f8c-4c07-qf8e-936823bf6bdb','nim' => '221524050', 'name' => 'Muhammad Hanif'],
            ['id' => 'bs483962-1f8c-4c08-rf8e-936823bf6bdb','nim' => '221524051', 'name' => 'Muhammad Rizki Nurmuttaqin'],
            ['id' => 'bs483961-1f8c-4c00-sf8e-936823bf6bdb','nim' => '221524052', 'name' => 'Naia Siti Az-zahra'],
            ['id' => 'bs483966-1f8c-4c01-tf8e-936823bf6bdb','nim' => '221524053', 'name' => 'Najib Alimudin Fajri'],
            ['id' => 'bs483965-1f8c-4c02-uf8e-936823bf6bdb','nim' => '221524054', 'name' => 'Niqa Nabila Nur Ihsani'],
            ['id' => 'bs483964-1f8c-4c03-vf8e-936823bf6bdb','nim' => '221524055', 'name' => 'Rafif Shabi Prasetyo'],
            ['id' => 'bs483963-1f8c-4c04-wf8e-936823bf6bdb','nim' => '221524056', 'name' => 'Revandi Faudiamar Putra Sitepu'],
            ['id' => 'bs483962-1f8c-4c05-xf8e-936823bf6bdb','nim' => '221524057', 'name' => 'Reza Maulana Aziiz'],
            ['id' => 'bs483961-1f8c-4c06-yf8e-936823bf6bdb','nim' => '221524058', 'name' => 'Salsabil Khoirunisa'],
            ['id' => 'bs483967-1f8c-4c07-zf8e-936823bf6bdb','nim' => '221524059', 'name' => 'Sarah'],
            ['id' => 'bs483968-1f8c-4c08-ef8e-936823bf6bdb','nim' => '221524060', 'name' => 'Septyana Agustina'],
            ['id' => 'bs483969-1f8c-4c09-rf8e-936823bf6bdb','nim' => '221524061', 'name' => 'Thoriq Muhammad Fadhli'],
            ['id' => 'bs483960-1f8c-4c00-tf8e-936823bf6bdb','nim' => '221524062', 'name' => 'Yusuf'],
            ['id' => 'bs483961-1f8c-4c01-qf8e-936823bf6bdb','nim' => '221524063', 'name' => 'Zahran Anugerah Rizqullah'],

        ];

        foreach ($students as $student) {
            DB::table('users')->insert([
                'id' => $student['id'],
                'name' => $student['name'],
                'email' => strtolower(str_replace(' ', '', $student['name'])) . '@proyek.com',
                'password' => bcrypt('qwerty123'),
                'role' => 'mahasiswa',
                'remember_token' => null,
            ]);
        }
    }
}
