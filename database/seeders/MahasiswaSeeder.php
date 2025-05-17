<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MahasiswaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $classIdA = 'be6ad5a-a8e7-4654-aa65-1a3301a394ab'; //Kelas A
        $classIdB = 'be6ad5a-a8e7-4654-aa65-1a3301a394bc'; //Kelas B

        DB::table('mahasiswa')->insert([
        ]);

        $students = [

            ['id' => 'sa483968-7f8c-1c05-af8e-936823bf1bda','nim' => '221524001', 'name' => 'Adinda Fauzia Puspita', 'class_id' => $classIdA],
            ['id' => 'sa483968-7f8c-1c05-af8e-936823bf2bdb','nim' => '221524002', 'name' => 'Akmal Goniyyu Hartono', 'class_id' => $classIdA],
            ['id' => 'sa483968-7f8c-1c05-af8e-936823bf3bdc','nim' => '221524003', 'name' => 'Aryo Rakatama', 'class_id' => $classIdA],
            ['id' => 'sa483968-7f8c-2c05-af8e-936823bf4bdd','nim' => '221524004', 'name' => 'Dafa Alfarizki Pratama', 'class_id' => $classIdA],
            ['id' => 'sa483968-7f8c-3c05-af8e-936823bf6bdf','nim' => '221524006', 'name' => 'Dean Adrian Baihaqi', 'class_id' => $classIdA],
            ['id' => 'sa483968-7f8c-5c05-af8e-936823bf8bdh','nim' => '221524008', 'name' => 'Farhan Muhammad Yasin', 'class_id' => $classIdA],
            ['id' => 'sa483968-7f8c-6c05-af8e-936823bf9bdi','nim' => '221524009', 'name' => 'Farrel Keiza Muhammad Yamin Putra', 'class_id' => $classIdA],
            ['id' => 'sa483968-7f8c-7c05-af8e-936823bf1bdj','nim' => '221524010', 'name' => 'Farrel Rahandika', 'class_id' => $classIdA],
            ['id' => 'sa483968-7f8c-6c05-af8e-936823bf2bdk','nim' => '221524011', 'name' => 'Hasna Fitriyani Khairunissa', 'class_id' => $classIdA],
            ['id' => 'sa483968-7f8c-7c05-af8e-936823bf3bdl','nim' => '221524012', 'name' => 'Muhamad Fahrizal Al Zaelani', 'class_id' => $classIdA],
            ['id' => 'sa483968-7f8c-8c05-af8e-936823bf4bdm','nim' => '221524013', 'name' => 'Muhamad Firman Firdaus', 'class_id' => $classIdA],
            ['id' => 'sa483968-7f8c-9c05-af8e-936823bf5bdn','nim' => '221524014', 'name' => 'Muhamad Mathar Rizqi', 'class_id' => $classIdA],
            ['id' => 'sa483968-7f8c-0c05-af8e-936823bf6bdo','nim' => '221524015', 'name' => 'Muhamad Rendi Zul Fauzi', 'class_id' => $classIdA],
            ['id' => 'sa483968-7f8c-1c05-af8e-936823bf7bdp','nim' => '221524016', 'name' => 'Muhammad Alvin Abdul Rozak Kusuma Putra', 'class_id' => $classIdA],
            ['id' => 'sa483968-7f8c-2c05-af8e-936823bf8bdq','nim' => '221524017', 'name' => 'Muhammad Alvyn Adhianto', 'class_id' => $classIdA],
            ['id' => 'sa483968-7f8c-3c05-af8e-936823bf9bdr','nim' => '221524018', 'name' => 'Muhammad Azharuddin Hamid', 'class_id' => $classIdA],
            ['id' => 'sa483968-7f8c-4c05-af8e-936823bf1bds','nim' => '221524019', 'name' => 'Muhammad Fikri Nur Sya bani', 'class_id' => $classIdA],
            ['id' => 'sa483968-7f8c-5c05-af8e-936823bf2bdt','nim' => '221524020', 'name' => 'Muhammad Raihan Fasya Mian', 'class_id' => $classIdA],
            ['id' => 'sa483968-7f8c-6c05-af8e-936823bf3bdu','nim' => '221524021', 'name' => 'Muhammad Rama Nurimani', 'class_id' => $classIdA],
            ['id' => 'sa483968-7f8c-7c05-af8e-936823bf4bdv','nim' => '221524022', 'name' => 'Mutiara Dwi Salma', 'class_id' => $classIdA],
            ['id' => 'sa483968-7f8c-8c05-af8e-936823bf5bdw','nim' => '221524023', 'name' => 'Naffa Lenteranisa', 'class_id' => $classIdA],
            ['id' => 'sa483968-7f8c-9c05-af8e-936823bf6bdx','nim' => '221524024', 'name' => 'Naila Saniyah Nur aini', 'class_id' => $classIdA],
            ['id' => 'sa483968-7f8c-9c05-af8e-936823bf8bdz','nim' => '221524026', 'name' => 'Rahma Divina', 'class_id' => $classIdA],
            ['id' => 'sa483968-7f8c-8c05-af8e-936823bf9bda','nim' => '221524027', 'name' => 'Rayhan Fanez Fathiadi', 'class_id' => $classIdA],
            ['id' => 'sa483968-7f8c-7c05-af8e-936823bf0bdb','nim' => '221524028', 'name' => 'Retryanzani Dwi Fauzan', 'class_id' => $classIdA],
            ['id' => 'sa483968-7f8c-6c05-af8e-936823bf1bdc','nim' => '221524029', 'name' => 'Rohiid Naufal Juliardi', 'class_id' => $classIdA],
            ['id' => 'sa483968-7f8c-5c05-af8e-936823bf2bdd','nim' => '221524030', 'name' => 'Roy Aziz Barera', 'class_id' => $classIdA],
            ['id' => 'sa483968-7f8c-4c05-af8e-936823bf3bde','nim' => '221524031', 'name' => 'Saabiq Muhyiyuddin Aulawi', 'class_id' => $classIdA],
            ['id' => 'sa483968-7f8c-3c05-af8e-936823bf4bdf','nim' => '221524032', 'name' => 'Welsya Almaira Indra', 'class_id' => $classIdA],
    //         // D4-B
            ['id' => 'bs483969-1f8c-4c01-af8e-936823bf6bdb','nim' => '221524033', 'name' => 'Alisha Nara Chandrakirana', 'class_id' => $classIdB],
            ['id' => 'bs483968-1f8c-4c02-bf8e-936823bf6bdb','nim' => '221524034', 'name' => 'Arnanda Prasatya', 'class_id' => $classIdB],
            ['id' => 'bs483967-1f8c-4c03-cf8e-936823bf6bdb','nim' => '221524035', 'name' => 'Asri Husnul Rosadi', 'class_id' => $classIdB],
            ['id' => 'bs483966-1f8c-4c04-df8e-936823bf6bdb','nim' => '221524036', 'name' => 'Banteng Harisantoso', 'class_id' => $classIdB],
            ['id' => 'bs483965-1f8c-4c05-ef8e-936823bf6bdb','nim' => '221524037', 'name' => 'Bhisma Chandra Yudha Setiawan', 'class_id' => $classIdB],
            ['id' => 'bs483963-1f8c-4c07-gf8e-936823bf6bdb','nim' => '221524039', 'name' => 'Farhan Muhammad Luthfi', 'class_id' => $classIdB],
            ['id' => 'bs483962-1f8c-4c08-hf8e-936823bf6bdb','nim' => '221524040', 'name' => 'Faris Abulkhoir', 'class_id' => $classIdB],
            ['id' => 'bs483961-1f8c-4c09-if8e-936823bf6bdb','nim' => '221524041', 'name' => 'Ferdi Ahmad Ariesta', 'class_id' => $classIdB],
            ['id' => 'bs483960-1f8c-4c00-jf8e-936823bf6bdb','nim' => '221524042', 'name' => 'Jeihan Ilham Kusumawardhana', 'class_id' => $classIdB],
            ['id' => 'bs483969-1f8c-4c01-kf8e-936823bf6bdb','nim' => '221524043', 'name' => 'Keanu Rayhan Harits', 'class_id' => $classIdB],
            ['id' => 'ts483978-1f8c-4c01-kf8e-936823bf6bdb','nim' => '221524044', 'name' => 'Mahardika Pratama', 'class_id' => $classIdB],
            ['id' => 'bs483968-1f8c-4c02-lf8e-936823bf6bdb','nim' => '221524045', 'name' => 'Mochamad Fathur Rabbani', 'class_id' => $classIdB],
            ['id' => 'bs483966-1f8c-4c04-nf8e-936823bf6bdb','nim' => '221524046', 'name' => 'Muhamad Agim', 'class_id' => $classIdB],
            ['id' => 'bs483965-1f8c-4c05-of8e-936823bf6bdb','nim' => '221524047', 'name' => 'Muhamad Fahri Yuwan Dwi Putra', 'class_id' => $classIdB],
            ['id' => 'bs483964-1f8c-4c06-pf8e-936823bf6bdb','nim' => '221524049', 'name' => 'Muhammad Daffa', 'class_id' => $classIdB],
            ['id' => 'bs483963-1f8c-4c07-qf8e-936823bf6bdb','nim' => '221524050', 'name' => 'Muhammad Hanif', 'class_id' => $classIdB],
            ['id' => 'bs483962-1f8c-4c08-rf8e-936823bf6bdb','nim' => '221524051', 'name' => 'Muhammad Rizki Nurmuttaqin', 'class_id' => $classIdB],
            ['id' => 'bs483961-1f8c-4c00-sf8e-936823bf6bdb','nim' => '221524052', 'name' => 'Naia Siti Az-zahra', 'class_id' => $classIdB],
            ['id' => 'bs483966-1f8c-4c01-tf8e-936823bf6bdb','nim' => '221524053', 'name' => 'Najib Alimudin Fajri', 'class_id' => $classIdB],
            ['id' => 'bs483965-1f8c-4c02-uf8e-936823bf6bdb','nim' => '221524054', 'name' => 'Niqa Nabila Nur Ihsani', 'class_id' => $classIdB],
            ['id' => 'bs483964-1f8c-4c03-vf8e-936823bf6bdb','nim' => '221524055', 'name' => 'Rafif Shabi Prasetyo', 'class_id' => $classIdB],
            ['id' => 'bs483963-1f8c-4c04-wf8e-936823bf6bdb','nim' => '221524056', 'name' => 'Revandi Faudiamar Putra Sitepu', 'class_id' => $classIdB],
            ['id' => 'bs483962-1f8c-4c05-xf8e-936823bf6bdb','nim' => '221524057', 'name' => 'Reza Maulana Aziiz', 'class_id' => $classIdB],
            ['id' => 'bs483961-1f8c-4c06-yf8e-936823bf6bdb','nim' => '221524058', 'name' => 'Salsabil Khoirunisa', 'class_id' => $classIdB],
            ['id' => 'bs483967-1f8c-4c07-zf8e-936823bf6bdb','nim' => '221524059', 'name' => 'Sarah', 'class_id' => $classIdB],
            ['id' => 'bs483968-1f8c-4c08-ef8e-936823bf6bdb','nim' => '221524060', 'name' => 'Septyana Agustina', 'class_id' => $classIdB],
            ['id' => 'bs483969-1f8c-4c09-rf8e-936823bf6bdb','nim' => '221524061', 'name' => 'Thoriq Muhammad Fadhli', 'class_id' => $classIdB],
            ['id' => 'bs483960-1f8c-4c00-tf8e-936823bf6bdb','nim' => '221524062', 'name' => 'Yusuf', 'class_id' => $classIdB],
            ['id' => 'bs483961-1f8c-4c01-qf8e-936823bf6bdb','nim' => '221524063', 'name' => 'Zahran Anugerah Rizqullah', 'class_id' => $classIdB],

        ];

        foreach ($students as $student) {
            $email = strtolower(str_replace(' ', '', $student['name'])) . '@proyek.com'; // Sesuai dengan UserSeeder
            $user = DB::table('users')->where('email', $email)->first(); // Cari berdasarkan email

            if ($user) {
                DB::table('mahasiswa')->insert([
                    'id' => $student['id'],
                    'user_id' => $user->id,
                    'class_id' => $student['class_id'],
                    'nim' => $student['nim'],
                ]);
            }
        }
    }
}
