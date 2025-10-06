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
        // $dosens = [
        //     ['id' => 'us483968-7f8c-4c01-af8a-936823bf6bda', 'nip' => '12345678 901234 5 001', 'name' => 'Dosen_1', 'email' => 'dosen1@proyek.com'],
        //     ['id' => 'us483968-7f8c-4c02-af8b-936823bf6bdi', 'nip' => '198903252019032023', 'name' => 'Sri Ratna Wulan, S.Pd., M.T.', 'email' => 'sw@proyek.com'],
        //     ['id' => 'us483968-7f8c-4c03-af8c-936823bf6bdu', 'nip' => '199003022019032019', 'name' => 'Rahil Jumiyani, S.ST., M.Sc', 'email' => 'ra@proyek.com'],
        //     ['id' => 'us483968-7f8c-4c04-af8d-936823bf6bde', 'nip' => '198906102019032019', 'name' => 'Asri Maspupah, S.S.T., M.T.', 'email' => 'am@proyek.com'],
        //     ['id' => 'us483968-7f8c-4c05-af8e-936823bf6bdo', 'nip' => '19661018 199512 1 001', 'name' => 'Joe Lian Min, M.Eng.', 'email' => 'jo@proyek.com'],
        //     ['id' => 'be483968-7f8c-4c05-af8e-936823bf6bdu', 'nip' => '199301062019031017', 'name' => 'Lukmannul Hakim Firdaus, S.Kom., M.T.', 'email' => 'lh@proyek.com'],
        //     ['id' => 'rs483968-7f8c-4c05-af8e-936823bf6bdk', 'nip' => '19800419 200501 1 002', 'name' => 'Irwan Setiawan, S.Si., M.T.', 'email' => 'is@proyek.com'],
        // ];
        // foreach ($dosens as $dosen) {
        //     DB::table('users')->insert([
        //         'id' => $dosen['id'],
        //         'name' => $dosen['name'],
        //         'email' => $dosen['email'],
        //         'password' => bcrypt("qwerty123"),
        //         'role' => 'dosen',
        //         'remember_token' => null,
        //     ]);
        // }

        // $students = [
        //     //         // D4-A
        //     ['id' => 'sa483968-7f8c-1c05-af8e-936823bf1bda', 'nim' => '221524001', 'name' => 'Adinda Fauzia Puspita'],
        //     ['id' => 'sa483968-7f8c-1c05-af8e-936823bf2bdb', 'nim' => '221524002', 'name' => 'Akmal Goniyyu Hartono'],
        //     ['id' => 'sa483968-7f8c-1c05-af8e-936823bf3bdc', 'nim' => '221524003', 'name' => 'Aryo Rakatama'],
        //     ['id' => 'sa483968-7f8c-2c05-af8e-936823bf4bdd', 'nim' => '221524004', 'name' => 'Dafa Alfarizki Pratama'],
        //     ['id' => 'sa483968-7f8c-3c05-af8e-936823bf6bdf', 'nim' => '221524006', 'name' => 'Dean Adrian Baihaqi'],
        //     ['id' => 'sa483968-7f8c-5c05-af8e-936823bf8bdh', 'nim' => '221524008', 'name' => 'Farhan Muhammad Yasin'],
        //     ['id' => 'sa483968-7f8c-6c05-af8e-936823bf9bdi', 'nim' => '221524009', 'name' => 'Farrel Keiza Muhammad Yamin Putra'],
        //     ['id' => 'sa483968-7f8c-7c05-af8e-936823bf1bdj', 'nim' => '221524010', 'name' => 'Farrel Rahandika'],
        //     ['id' => 'sa483968-7f8c-6c05-af8e-936823bf2bdk', 'nim' => '221524011', 'name' => 'Hasna Fitriyani Khairunissa'],
        //     ['id' => 'sa483968-7f8c-7c05-af8e-936823bf3bdl', 'nim' => '221524012', 'name' => 'Muhamad Fahrizal Al Zaelani'],
        //     ['id' => 'sa483968-7f8c-8c05-af8e-936823bf4bdm', 'nim' => '221524013', 'name' => 'Muhamad Firman Firdaus'],
        //     ['id' => 'sa483968-7f8c-9c05-af8e-936823bf5bdn', 'nim' => '221524014', 'name' => 'Muhamad Mathar Rizqi'],
        //     ['id' => 'sa483968-7f8c-0c05-af8e-936823bf6bdo', 'nim' => '221524015', 'name' => 'Muhamad Rendi Zul Fauzi'],
        //     ['id' => 'sa483968-7f8c-1c05-af8e-936823bf7bdp', 'nim' => '221524016', 'name' => 'Muhammad Alvin Abdul Rozak Kusuma Putra'],
        //     ['id' => 'sa483968-7f8c-2c05-af8e-936823bf8bdq', 'nim' => '221524017', 'name' => 'Muhammad Alvyn Adhianto'],
        //     ['id' => 'sa483968-7f8c-3c05-af8e-936823bf9bdr', 'nim' => '221524018', 'name' => 'Muhammad Azharuddin Hamid'],
        //     ['id' => 'sa483968-7f8c-4c05-af8e-936823bf1bds', 'nim' => '221524019', 'name' => 'Muhammad Fikri Nur Sya bani'],
        //     ['id' => 'sa483968-7f8c-5c05-af8e-936823bf2bdt', 'nim' => '221524020', 'name' => 'Muhammad Raihan Fasya Mian'],
        //     ['id' => 'sa483968-7f8c-6c05-af8e-936823bf3bdu', 'nim' => '221524021', 'name' => 'Muhammad Rama Nurimani'],
        //     ['id' => 'sa483968-7f8c-7c05-af8e-936823bf4bdv', 'nim' => '221524022', 'name' => 'Mutiara Dwi Salma'],
        //     ['id' => 'sa483968-7f8c-8c05-af8e-936823bf5bdw', 'nim' => '221524023', 'name' => 'Naffa Lenteranisa'],
        //     ['id' => 'sa483968-7f8c-9c05-af8e-936823bf6bdx', 'nim' => '221524024', 'name' => 'Naila Saniyah Nur aini'],
        //     ['id' => 'sa483968-7f8c-9c05-af8e-936823bf8bdz', 'nim' => '221524026', 'name' => 'Rahma Divina'],
        //     ['id' => 'sa483968-7f8c-8c05-af8e-936823bf9bda', 'nim' => '221524027', 'name' => 'Rayhan Fanez Fathiadi'],
        //     ['id' => 'sa483968-7f8c-7c05-af8e-936823bf0bdb', 'nim' => '221524028', 'name' => 'Retryanzani Dwi Fauzan'],
        //     ['id' => 'sa483968-7f8c-6c05-af8e-936823bf1bdc', 'nim' => '221524029', 'name' => 'Rohiid Naufal Juliardi'],
        //     ['id' => 'sa483968-7f8c-5c05-af8e-936823bf2bdd', 'nim' => '221524030', 'name' => 'Roy Aziz Barera'],
        //     ['id' => 'sa483968-7f8c-4c05-af8e-936823bf3bde', 'nim' => '221524031', 'name' => 'Saabiq Muhyiyuddin Aulawi'],
        //     ['id' => 'sa483968-7f8c-3c05-af8e-936823bf4bdf', 'nim' => '221524032', 'name' => 'Welsya Almaira Indra'],
        //     //         // D4-B
        //     ['id' => 'bs483969-1f8c-4c01-af8e-936823bf6bdb', 'nim' => '221524033', 'name' => 'Alisha Nara Chandrakirana'],
        //     ['id' => 'bs483968-1f8c-4c02-bf8e-936823bf6bdb', 'nim' => '221524034', 'name' => 'Arnanda Prasatya'],
        //     ['id' => 'bs483967-1f8c-4c03-cf8e-936823bf6bdb', 'nim' => '221524035', 'name' => 'Asri Husnul Rosadi'],
        //     ['id' => 'bs483966-1f8c-4c04-df8e-936823bf6bdb', 'nim' => '221524036', 'name' => 'Banteng Harisantoso'],
        //     ['id' => 'bs483965-1f8c-4c05-ef8e-936823bf6bdb', 'nim' => '221524037', 'name' => 'Bhisma Chandra Yudha Setiawan'],
        //     ['id' => 'bs483963-1f8c-4c07-gf8e-936823bf6bdb', 'nim' => '221524039', 'name' => 'Farhan Muhammad Luthfi'],
        //     ['id' => 'bs483962-1f8c-4c08-hf8e-936823bf6bdb', 'nim' => '221524040', 'name' => 'Faris Abulkhoir'],
        //     ['id' => 'bs483961-1f8c-4c09-if8e-936823bf6bdb', 'nim' => '221524041', 'name' => 'Ferdi Ahmad Ariesta'],
        //     ['id' => 'bs483960-1f8c-4c00-jf8e-936823bf6bdb', 'nim' => '221524042', 'name' => 'Jeihan Ilham Kusumawardhana'],
        //     ['id' => 'bs483969-1f8c-4c01-kf8e-936823bf6bdb', 'nim' => '221524043', 'name' => 'Keanu Rayhan Harits'],
        //     ['id' => 'ts483978-1f8c-4c01-kf8e-936823bf6bdb', 'nim' => '221524044', 'name' => 'Mahardika Pratama'],
        //     ['id' => 'bs483968-1f8c-4c02-lf8e-936823bf6bdb', 'nim' => '221524045', 'name' => 'Mochamad Fathur Rabbani'],
        //     ['id' => 'bs483967-1f8c-4c03-mf8e-936823bf6bdb', 'nim' => '221524046', 'name' => 'Muhamad Agim'],
        //     ['id' => 'bs483965-1f8c-4c05-of8e-936823bf6bdb', 'nim' => '221524047', 'name' => 'Muhamad Fahri Yuwan Dwi Putra'],
        //     ['id' => 'bs483964-1f8c-4c06-pf8e-936823bf6bdb', 'nim' => '221524049', 'name' => 'Muhammad Daffa'],
        //     ['id' => 'bs483963-1f8c-4c07-qf8e-936823bf6bdb', 'nim' => '221524050', 'name' => 'Muhammad Hanif'],
        //     ['id' => 'bs483962-1f8c-4c08-rf8e-936823bf6bdb', 'nim' => '221524051', 'name' => 'Muhammad Rizki Nurmuttaqin'],
        //     ['id' => 'bs483961-1f8c-4c00-sf8e-936823bf6bdb', 'nim' => '221524052', 'name' => 'Naia Siti Az-zahra'],
        //     ['id' => 'bs483966-1f8c-4c01-tf8e-936823bf6bdb', 'nim' => '221524053', 'name' => 'Najib Alimudin Fajri'],
        //     ['id' => 'bs483965-1f8c-4c02-uf8e-936823bf6bdb', 'nim' => '221524054', 'name' => 'Niqa Nabila Nur Ihsani'],
        //     ['id' => 'bs483964-1f8c-4c03-vf8e-936823bf6bdb', 'nim' => '221524055', 'name' => 'Rafif Shabi Prasetyo'],
        //     ['id' => 'bs483963-1f8c-4c04-wf8e-936823bf6bdb', 'nim' => '221524056', 'name' => 'Revandi Faudiamar Putra Sitepu'],
        //     ['id' => 'bs483962-1f8c-4c05-xf8e-936823bf6bdb', 'nim' => '221524057', 'name' => 'Reza Maulana Aziiz'],
        //     ['id' => 'bs483961-1f8c-4c06-yf8e-936823bf6bdb', 'nim' => '221524058', 'name' => 'Salsabil Khoirunisa'],
        //     ['id' => 'bs483967-1f8c-4c07-zf8e-936823bf6bdb', 'nim' => '221524059', 'name' => 'Sarah'],
        //     ['id' => 'bs483968-1f8c-4c08-ef8e-936823bf6bdb', 'nim' => '221524060', 'name' => 'Septyana Agustina'],
        //     ['id' => 'bs483969-1f8c-4c09-rf8e-936823bf6bdb', 'nim' => '221524061', 'name' => 'Thoriq Muhammad Fadhli'],
        //     ['id' => 'bs483960-1f8c-4c00-tf8e-936823bf6bdb', 'nim' => '221524062', 'name' => 'Yusuf'],
        //     ['id' => 'bs483961-1f8c-4c01-qf8e-936823bf6bdb', 'nim' => '221524063', 'name' => 'Zahran Anugerah Rizqullah'],

        //     ['id' => 'aa483968-7f8c-1c05-af8e-936823bf1bda', 'nim' => '241511001', 'name' => 'Andi Putra Wijaya'],
        //     ['id' => 'aa483968-7f8c-1c05-af8e-936823bf2bdb', 'nim' => '241511002', 'name' => 'Arief F-Sa Wijaya'],
        //     ['id' => 'aa483968-7f8c-1c05-af8e-936823bf3bdc', 'nim' => '241511003', 'name' => 'Arnold Billy Kresnawan'],
        //     ['id' => 'aa483968-7f8c-2c05-af8e-936823bf4bdd', 'nim' => '241511004', 'name' => 'Chinta Karina Khairunissa'],
        //     ['id' => 'aa483968-7f8c-3c05-af8e-936823bf5bde', 'nim' => '241511005', 'name' => 'Christian Goklas Natanael Sitorus'],
        //     ['id' => 'aa483968-7f8c-3c05-af8e-936823bf6bdf', 'nim' => '241511006', 'name' => 'Dwika Bayu Adinata'],
        //     ['id' => 'aa483968-7f8c-5c05-af8e-936823bf7bdg', 'nim' => '241511007', 'name' => 'Emir Althaf Rasyid Kantaprawira'],
        //     ['id' => 'aa483968-7f8c-5c05-af8e-936823bf8bdh', 'nim' => '241511008', 'name' => 'Farell Daffa Aditya'],
        //     ['id' => 'aa483968-7f8c-6c05-af8e-936823bf9bdi', 'nim' => '241511009', 'name' => 'Faridha Zahiya'],
        //     ['id' => 'aa483968-7f8c-7c05-af8e-936823bf1bdj', 'nim' => '241511010', 'name' => 'Farras Fadhil Syafiq'],
        //     ['id' => 'aa483968-7f8c-6c05-af8e-936823bf2bdk', 'nim' => '241511011', 'name' => 'Fawwaz Naufal Anwar'],
        //     ['id' => 'aa483968-7f8c-7c05-af8e-936823bf3bdl', 'nim' => '241511012', 'name' => 'Gilang Aditya Sumarna'],
        //     ['id' => 'aa483968-7f8c-8c05-af8e-936823bf4bdm', 'nim' => '241511013', 'name' => 'Hilmi Mahdani Bilda'],
        //     ['id' => 'aa483968-7f8c-9c05-af8e-936823bf5bdn', 'nim' => '241511014', 'name' => 'Irvan Supriadi Situmorang'],
        //     ['id' => 'aa483968-7f8c-0c05-af8e-936823bf6bdo', 'nim' => '241511015', 'name' => 'Marrely Salsa Kasih Risky'],
        //     ['id' => 'aa483968-7f8c-1c05-af8e-936823bf7bdp', 'nim' => '241511016', 'name' => 'Maulana Ishak'],
        //     ['id' => 'aa483968-7f8c-2c05-af8e-936823bf8bdq', 'nim' => '241511017', 'name' => 'Muhammad Faliq Shiddiq Azzaki'],
        //     ['id' => 'aa483968-7f8c-3c05-af8e-936823bf9bdr', 'nim' => '241511018', 'name' => 'Muhammad Hanif 24'],
        //     ['id' => 'aa483968-7f8c-4c05-af8e-936823bf1bds', 'nim' => '241511019', 'name' => 'Muhammad Hasbi Hardian'],
        //     ['id' => 'aa483968-7f8c-5c05-af8e-936823bf2bdt', 'nim' => '241511020', 'name' => 'Muhammad Nabiil Fanezi'],
        //     ['id' => 'aa483968-7f8c-6c05-af8e-936823bf3bdu', 'nim' => '241511021', 'name' => 'Nabil Mudzaki Al Muharam'],
        //     ['id' => 'aa483968-7f8c-7c05-af8e-936823bf4bdv', 'nim' => '241511022', 'name' => 'Naira Tahira'],
        //     ['id' => 'aa483968-7f8c-8c05-af8e-936823bf5bdw', 'nim' => '241511023', 'name' => 'Nashwa Fathia Fasha'],
        //     ['id' => 'aa483968-7f8c-9c05-af8e-936823bf6bdx', 'nim' => '241511024', 'name' => 'Nazwa Ramadhani'],
        //     ['id' => 'aa483968-7f8c-1c05-af8e-936823bf7bdy', 'nim' => '241511025', 'name' => 'Raffi Fauzi Hermawan'],
        //     ['id' => 'aa483968-7f8c-9c05-af8e-936823bf8bdz', 'nim' => '241511026', 'name' => 'Suci Sulistiawati'],
        //     ['id' => 'aa483968-7f8c-8c05-af8e-936823bf9bda', 'nim' => '241511027', 'name' => 'Tamam Hisabulah'],
        //     ['id' => 'aa483968-7f8c-7c05-af8e-936823bf0bdb', 'nim' => '241511028', 'name' => 'Tsinan Arkan'],
        //     ['id' => 'aa483968-7f8c-6c05-af8e-936823bf1bdc', 'nim' => '241511029', 'name' => 'Wafi Ahlam Rizqulloh'],
        //     ['id' => 'aa483968-7f8c-5c05-af8e-936823bf2bdd', 'nim' => '241511030', 'name' => 'Zahwa Nazala Khalisan Herdiyana'],
        //     ['id' => 'aa483968-7f8c-4c05-af8e-936823bf3bde', 'nim' => '241511031', 'name' => 'Zainandhi Nur Fathurrohman'],
        //     ['id' => 'aa483968-7f8c-3c05-af8e-936823bf4bdf', 'nim' => '241511032', 'name' => 'Zidan Taufiqurrahman'],

        //     // D3-B
        //     ['id' => 'ba483969-1f8c-4c01-af8e-936823bf6bdb', 'nim' => '241511033', 'name' => 'Abdurahman Nur Fadilah'],
        //     ['id' => 'ba483968-1f8c-4c02-bf8e-936823bf6bdb', 'nim' => '241511034', 'name' => 'Adjie Ali Nurfizal'],
        //     ['id' => 'ba483967-1f8c-4c03-cf8e-936823bf6bdb', 'nim' => '241511035', 'name' => 'Ahmad Riyadh Almaliki'],
        //     ['id' => 'ba483966-1f8c-4c04-df8e-936823bf6bdb', 'nim' => '241511036', 'name' => 'Akmal Rezaqi Al-Farhan'],
        //     ['id' => 'ba483965-1f8c-4c05-ef8e-936823bf6bdb', 'nim' => '241511037', 'name' => 'Andreas Devan Pandu Narasamdya'],
        //     ['id' => 'ba483964-1f8c-4c06-ff8e-936823bf6bdb', 'nim' => '241511038', 'name' => 'Arman Yusuf Rifandi'],
        //     ['id' => 'ba483963-1f8c-4c07-gf8e-936823bf6bdb', 'nim' => '241511039', 'name' => 'Azzam Fadlurrahman'],
        //     ['id' => 'ba483962-1f8c-4c08-hf8e-936823bf6bdb', 'nim' => '241511040', 'name' => 'Dimas Rizal Ramadhani'],
        //     ['id' => 'ba483961-1f8c-4c09-if8e-936823bf6bdb', 'nim' => '241511041', 'name' => 'Dinanda Khayra Hutama'],
        //     ['id' => 'ba483960-1f8c-4c00-jf8e-936823bf6bdb', 'nim' => '241511042', 'name' => 'Fahraj Ananta Aulia Arkan'],
        //     ['id' => 'ba483969-1f8c-4c01-kf8e-936823bf6bdb', 'nim' => '241511043', 'name' => 'Fiandra Putera Mardani'],
        //     ['id' => 'ba483968-1f8c-4c02-lf8e-936823bf6bdb', 'nim' => '241511045', 'name' => 'Hafizh Andhika Putra'],
        //     ['id' => 'ba483967-1f8c-4c03-mf8e-936823bf6bdb', 'nim' => '241511046', 'name' => 'Idham Khalid'],
        //     ['id' => 'ba483966-1f8c-4c04-nf8e-936823bf6bdb', 'nim' => '241511047', 'name' => 'Mahesa Fazrie Mahardhika Gunadi'],
        //     ['id' => 'ba483964-1f8c-4c06-pf8e-936823bf6bdb', 'nim' => '241511049', 'name' => 'Mohammad Jibril Fathi Farhan Al Ghifari'],
        //     ['id' => 'ba483963-1f8c-4c07-qf8e-936823bf6bdb', 'nim' => '241511050', 'name' => 'Muhamad Raditya Novandrian'],
        //     ['id' => 'ba483962-1f8c-4c08-rf8e-936823bf6bdb', 'nim' => '241511051', 'name' => 'Muhammad Faiz Ramdhani'],
        //     ['id' => 'ba483961-1f8c-4c00-sf8e-936823bf6bdb', 'nim' => '241511052', 'name' => 'Muhammad Naufal Alfarizky'],
        //     ['id' => 'ba483966-1f8c-4c01-tf8e-936823bf6bdb', 'nim' => '241511053', 'name' => 'Muhammad Naufal Nurmaryadi'],
        //     ['id' => 'ba483965-1f8c-4c02-uf8e-936823bf6bdb', 'nim' => '241511054', 'name' => 'Nauval Hilmy Firjatullah'],
        //     ['id' => 'ba483964-1f8c-4c03-vf8e-936823bf6bdb', 'nim' => '241511055', 'name' => 'Nazriel Ramdhani'],
        //     ['id' => 'ba483963-1f8c-4c04-wf8e-936823bf6bdb', 'nim' => '241511056', 'name' => 'Raihana Aisha Az-Zahra'],
        //     ['id' => 'ba483962-1f8c-4c05-xf8e-936823bf6bdb', 'nim' => '241511057', 'name' => 'Revaldi Ardhi Prasetyo'],
        //     ['id' => 'ba483961-1f8c-4c06-yf8e-936823bf6bdb', 'nim' => '241511058', 'name' => 'Revan Ramdani Permana'],
        //     ['id' => 'ba483967-1f8c-4c07-zf8e-936823bf6bdb', 'nim' => '241511059', 'name' => 'Ridho Sulistyo Saputro'],
        //     ['id' => 'ba483968-1f8c-4c08-ef8e-936823bf6bdb', 'nim' => '241511060', 'name' => 'Rifky Hermawan'],
        //     ['id' => 'ba483969-1f8c-4c09-rf8e-936823bf6bdb', 'nim' => '241511061', 'name' => 'Rina Permata Dewi'],
        //     ['id' => 'ba483960-1f8c-4c00-tf8e-936823bf6bdb', 'nim' => '241511062', 'name' => 'Salma Arifah Zahra'],
        //     ['id' => 'ba483961-1f8c-4c01-qf8e-936823bf6bdb', 'nim' => '241511063', 'name' => 'Samudra Putra Gunawan'],
        //     ['id' => 'ba483962-1f8c-4c02-lf8e-936823bf6bdb', 'nim' => '241511064', 'name' => 'Seruni Libertina Islami'],

        //     // D3-C
        //     ['id' => 'ca481968-7f8c-4c05-af8e-936823bf6ado', 'nim' => '241511065', 'name' => 'Ahmad Habib Muttaqin'],
        //     ['id' => 'ca482968-7f8c-4c05-af8e-936823bf6bdo', 'nim' => '241511066', 'name' => 'Alda Pujama'],
        //     ['id' => 'ca483968-7f8c-4c05-af8e-936823bf6cdo', 'nim' => '241511067', 'name' => 'Alexandrio Vega Bonito'],
        //     ['id' => 'ca484968-7f8c-4c05-af8e-936823bf6ddo', 'nim' => '241511068', 'name' => 'Andhini Widya Putri Wastika'],
        //     ['id' => 'ca485968-7f8c-4c05-af8e-936823bf6edo', 'nim' => '241511069', 'name' => 'Azkha Nazzala Prasadha Dies'],
        //     ['id' => 'ca486968-7f8c-4c05-af8e-936823bf6fdo', 'nim' => '241511070', 'name' => 'Dava Ramadhan'],
        //     ['id' => 'ca487968-7f8c-4c05-af8e-936823bf6gdo', 'nim' => '241511071', 'name' => 'Dzakir Tsabit Asy-Syafiq'],
        //     ['id' => 'ca488968-7f8c-4c05-af8e-936823bf6hdo', 'nim' => '241511072', 'name' => 'Ersya Hasby Satria'],
        //     ['id' => 'ca489968-7f8c-4c05-af8e-936823bf6ido', 'nim' => '241511073', 'name' => 'Fairuz Sheva Muhammad'],
        //     ['id' => 'ca481968-7f8c-4c05-af8e-936823bf6jdo', 'nim' => '241511074', 'name' => 'Fatimah Hawwa Alkhansa'],
        //     ['id' => 'ca482968-7f8c-4c05-af8e-936823bf6kdo', 'nim' => '241511075', 'name' => 'Gema Adzan Firdaus'],
        //     ['id' => 'ca483968-7f8c-4c05-af8e-936823bf6ldo', 'nim' => '241511076', 'name' => 'Hanifidin Ibrahim'],
        //     ['id' => 'ca484968-7f8c-4c05-af8e-936823bf6mdo', 'nim' => '241511077', 'name' => 'Helga Athifa Hidayat'],
        //     ['id' => 'ca485968-7f8c-4c05-af8e-936823bf6ndo', 'nim' => '241511078', 'name' => 'Hisyam Khaeru Umam'],
        //     ['id' => 'ca486968-7f8c-4c05-af8e-936823bf6odo', 'nim' => '241511079', 'name' => 'Ibnu Hilmi Athaillah'],
        //     ['id' => 'ca487968-7f8c-4c05-af8e-936823bf6pdo', 'nim' => '241511080', 'name' => 'Ikhsan Satriadi'],
        //     ['id' => 'ca488968-7f8c-4c05-af8e-936823bf6qdo', 'nim' => '241511081', 'name' => 'Muhamad Syahid'],
        //     ['id' => 'ca489968-7f8c-4c05-af8e-936823bf6rdo', 'nim' => '241511082', 'name' => 'Muhammad Brata Hadinata'],
        //     ['id' => 'ca480968-7f8c-4c05-af8e-936823bf6sdo', 'nim' => '241511083', 'name' => 'Muhammad Ihsan Ramadhan'],
        //     ['id' => 'ca481968-7f8c-4c05-af8e-936823bf6tdo', 'nim' => '241511084', 'name' => 'Muhammad Raihan Abubakar'],
        //     ['id' => 'ca482968-7f8c-4c05-af8e-936823bf6udo', 'nim' => '241511085', 'name' => 'Nezya Zulfa Fauziah'],
        //     ['id' => 'ca483968-7f8c-4c05-af8e-936823bf6vdo', 'nim' => '241511086', 'name' => 'Nike Kustiane'],
        //     ['id' => 'ca484968-7f8c-4c05-af8e-936823bf6wdo', 'nim' => '241511087', 'name' => 'Qlio Amanda Febriany'],
        //     ['id' => 'ca485968-7f8c-4c05-af8e-936823bf6xdo', 'nim' => '241511088', 'name' => 'Rahma Attaya Tamimah'],
        //     ['id' => 'ca486968-7f8c-4c05-af8e-936823bf6ydo', 'nim' => '241511089', 'name' => 'Rizky Satria Gunawan'],
        //     ['id' => 'ca487968-7f8c-4c05-af8e-936823bf6zdo', 'nim' => '241511090', 'name' => 'Siti Soviyyah'],
        //     ['id' => 'ca488968-7f8c-4c05-af8e-936823bf6ado', 'nim' => '241511091', 'name' => 'Varian Abidarma Syuhada'],
        //     ['id' => 'ca489968-7f8c-4c05-af8e-936823bf6bdo', 'nim' => '241511092', 'name' => 'Wyandhanu Maulidan Nugraha'],
        //     ['id' => 'ca480968-7f8c-4c05-af8e-936823bf6cdo', 'nim' => '241511093', 'name' => 'Yazid Alrasyid'],
        //     ['id' => 'ca484968-7f8c-4c05-af8e-736823bf6ddo', 'nim' => '241511094', 'name' => 'Zahra Aldila'],
        //     ['id' => 'ca483968-7f8c-4c05-af8e-936823bf6edo', 'nim' => '241511095', 'name' => 'Zakky Zhillan Muhammad Irsyad'],
        // ];

        // foreach ($students as $student) {
        //     DB::table('users')->insert([
        //         'id' => $student['id'],
        //         'name' => $student['name'],
        //         'email' => strtolower(str_replace(' ', '', $student['name'])) . '@proyek.com',
        //         'password' => bcrypt('qwerty123'),
        //         'role' => 'mahasiswa',
        //         'remember_token' => null,
        //     ]);
        // }
    }
}
