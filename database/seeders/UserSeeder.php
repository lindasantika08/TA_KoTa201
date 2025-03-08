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
            ['id' => 'us483968-7f8c-4c02-af8b-936823bf6bdi','nip' => '12345678 901234 5 002', 'name' => 'Dosen_2', 'email' => 'dosen2@proyek.com'],
            ['id' => 'us483968-7f8c-4c03-af8c-936823bf6bdu','nip' => '12345678 901234 5 003', 'name' => 'Dosen_3', 'email' => 'dosen3@proyek.com'],
            ['id' => 'us483968-7f8c-4c04-af8d-936823bf6bde','nip' => '12345678 901234 5 004', 'name' => 'Dosen_4', 'email' => 'dosen4@proyek.com'],
            ['id' => 'us483968-7f8c-4c05-af8e-936823bf6bdo','nip' => '12345678 901234 5 005', 'name' => 'Dosen_5', 'email' => 'dosen5@proyek.com'],
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
            // D3-A
            ['id' => 'sa483968-7f8c-1c05-af8e-936823bf1bda','nim' => '241511001', 'name' => 'Andi Putra Wijaya'],
            ['id' => 'sa483968-7f8c-1c05-af8e-936823bf2bdb','nim' => '241511002', 'name' => 'Arief F-Sa Wijaya'],
            ['id' => 'sa483968-7f8c-1c05-af8e-936823bf3bdc','nim' => '241511003', 'name' => 'Arnold Billy Kresnawan'],
            ['id' => 'sa483968-7f8c-2c05-af8e-936823bf4bdd','nim' => '241511004', 'name' => 'Chinta Karina Khairunissa'],
            ['id' => 'sa483968-7f8c-3c05-af8e-936823bf5bde','nim' => '241511005', 'name' => 'Christian Goklas Natanael Sitorus'],
            ['id' => 'sa483968-7f8c-3c05-af8e-936823bf6bdf','nim' => '241511006', 'name' => 'Dwika Bayu Adinata'],
            ['id' => 'sa483968-7f8c-5c05-af8e-936823bf7bdg','nim' => '241511007', 'name' => 'Emir Althaf Rasyid Kantaprawira'],
            ['id' => 'sa483968-7f8c-5c05-af8e-936823bf8bdh','nim' => '241511008', 'name' => 'Farell Daffa Aditya'],
            ['id' => 'sa483968-7f8c-6c05-af8e-936823bf9bdi','nim' => '241511009', 'name' => 'Faridha Zahiya'],
            ['id' => 'sa483968-7f8c-7c05-af8e-936823bf1bdj','nim' => '241511010', 'name' => 'Farras Fadhil Syafiq'],
            ['id' => 'sa483968-7f8c-6c05-af8e-936823bf2bdk','nim' => '241511011', 'name' => 'Fawwaz Naufal Anwar'],
            ['id' => 'sa483968-7f8c-7c05-af8e-936823bf3bdl','nim' => '241511012', 'name' => 'Gilang Aditya Sumarna'],
            ['id' => 'sa483968-7f8c-8c05-af8e-936823bf4bdm','nim' => '241511013', 'name' => 'Hilmi Mahdani Bilda'],
            ['id' => 'sa483968-7f8c-9c05-af8e-936823bf5bdn','nim' => '241511014', 'name' => 'Irvan Supriadi Situmorang'],
            ['id' => 'sa483968-7f8c-0c05-af8e-936823bf6bdo','nim' => '241511015', 'name' => 'Marrely Salsa Kasih Risky'],
            ['id' => 'sa483968-7f8c-1c05-af8e-936823bf7bdp','nim' => '241511016', 'name' => 'Maulana Ishak'],
            ['id' => 'sa483968-7f8c-2c05-af8e-936823bf8bdq','nim' => '241511017', 'name' => 'Muhammad Faliq Shiddiq Azzaki'],
            ['id' => 'sa483968-7f8c-3c05-af8e-936823bf9bdr','nim' => '241511018', 'name' => 'Muhammad Hanif'],
            ['id' => 'sa483968-7f8c-4c05-af8e-936823bf1bds','nim' => '241511019', 'name' => 'Muhammad Hasbi Hardian'],
            ['id' => 'sa483968-7f8c-5c05-af8e-936823bf2bdt','nim' => '241511020', 'name' => 'Muhammad Nabiil Fanezi'],
            ['id' => 'sa483968-7f8c-6c05-af8e-936823bf3bdu','nim' => '241511021', 'name' => 'Nabil Mudzaki Al Muharam'],
            ['id' => 'sa483968-7f8c-7c05-af8e-936823bf4bdv','nim' => '241511022', 'name' => 'Naira Tahira'],
            ['id' => 'sa483968-7f8c-8c05-af8e-936823bf5bdw','nim' => '241511023', 'name' => 'Nashwa Fathia Fasha'],
            ['id' => 'sa483968-7f8c-9c05-af8e-936823bf6bdx','nim' => '241511024', 'name' => 'Nazwa Ramadhani'],
            ['id' => 'sa483968-7f8c-1c05-af8e-936823bf7bdy','nim' => '241511025', 'name' => 'Raffi Fauzi Hermawan'],
            ['id' => 'sa483968-7f8c-9c05-af8e-936823bf8bdz','nim' => '241511026', 'name' => 'Suci Sulistiawati'],
            ['id' => 'sa483968-7f8c-8c05-af8e-936823bf9bda','nim' => '241511027', 'name' => 'Tamam Hisabulah'],
            ['id' => 'sa483968-7f8c-7c05-af8e-936823bf0bdb','nim' => '241511028', 'name' => 'Tsinan Arkan'],
            ['id' => 'sa483968-7f8c-6c05-af8e-936823bf1bdc','nim' => '241511029', 'name' => 'Wafi Ahlam Rizqulloh'],
            ['id' => 'sa483968-7f8c-5c05-af8e-936823bf2bdd','nim' => '241511030', 'name' => 'Zahwa Nazala Khalisan Herdiyana'],
            ['id' => 'sa483968-7f8c-4c05-af8e-936823bf3bde','nim' => '241511031', 'name' => 'Zainandhi Nur Fathurrohman'],
            ['id' => 'sa483968-7f8c-3c05-af8e-936823bf4bdf','nim' => '241511032', 'name' => 'Zidan Taufiqurrahman'],
            // D3-B
            ['id' => 'bs483969-1f8c-4c01-af8e-936823bf6bdb','nim' => '241511033', 'name' => 'Abdurahman Nur Fadilah'],
            ['id' => 'bs483968-1f8c-4c02-bf8e-936823bf6bdb','nim' => '241511034', 'name' => 'Adjie Ali Nurfizal'],
            ['id' => 'bs483967-1f8c-4c03-cf8e-936823bf6bdb','nim' => '241511035', 'name' => 'Ahmad Riyadh Almaliki'],
            ['id' => 'bs483966-1f8c-4c04-df8e-936823bf6bdb','nim' => '241511036', 'name' => 'Akmal Rezaqi Al-Farhan'],
            ['id' => 'bs483965-1f8c-4c05-ef8e-936823bf6bdb','nim' => '241511037', 'name' => 'Andreas Devan Pandu Narasamdya'],
            ['id' => 'bs483964-1f8c-4c06-ff8e-936823bf6bdb','nim' => '241511038', 'name' => 'Arman Yusuf Rifandi'],
            ['id' => 'bs483963-1f8c-4c07-gf8e-936823bf6bdb','nim' => '241511039', 'name' => 'Azzam Fadlurrahman'],
            ['id' => 'bs483962-1f8c-4c08-hf8e-936823bf6bdb','nim' => '241511040', 'name' => 'Dimas Rizal Ramadhani'],
            ['id' => 'bs483961-1f8c-4c09-if8e-936823bf6bdb','nim' => '241511041', 'name' => 'Dinanda Khayra Hutama'],
            ['id' => 'bs483960-1f8c-4c00-jf8e-936823bf6bdb','nim' => '241511042', 'name' => 'Fahraj Ananta Aulia Arkan'],
            ['id' => 'bs483969-1f8c-4c01-kf8e-936823bf6bdb','nim' => '241511043', 'name' => 'Fiandra Putera Mardani'],
            ['id' => 'bs483968-1f8c-4c02-lf8e-936823bf6bdb','nim' => '241511045', 'name' => 'Hafizh Andhika Putra'],
            ['id' => 'bs483967-1f8c-4c03-mf8e-936823bf6bdb','nim' => '241511046', 'name' => 'Idham Khalid'],
            ['id' => 'bs483966-1f8c-4c04-nf8e-936823bf6bdb','nim' => '241511047', 'name' => 'Mahesa Fazrie Mahardhika Gunadi'],
            ['id' => 'bs483965-1f8c-4c05-of8e-936823bf6bdb','nim' => '241511048', 'name' => 'Micky Ridho Pratama'],
            ['id' => 'bs483964-1f8c-4c06-pf8e-936823bf6bdb','nim' => '241511049', 'name' => 'Mohammad Jibril Fathi Farhan Al Ghifari'],
            ['id' => 'bs483963-1f8c-4c07-qf8e-936823bf6bdb','nim' => '241511050', 'name' => 'Muhamad Raditya Novandrian'],
            ['id' => 'bs483962-1f8c-4c08-rf8e-936823bf6bdb','nim' => '241511051', 'name' => 'Muhammad Faiz Ramdhani'],
            ['id' => 'bs483961-1f8c-4c00-sf8e-936823bf6bdb','nim' => '241511052', 'name' => 'Muhammad Naufal Alfarizky'],
            ['id' => 'bs483966-1f8c-4c01-tf8e-936823bf6bdb','nim' => '241511053', 'name' => 'Muhammad Naufal Nurmaryadi'],
            ['id' => 'bs483965-1f8c-4c02-uf8e-936823bf6bdb','nim' => '241511054', 'name' => 'Nauval Hilmy Firjatullah'],
            ['id' => 'bs483964-1f8c-4c03-vf8e-936823bf6bdb','nim' => '241511055', 'name' => 'Nazriel Ramdhani'],
            ['id' => 'bs483963-1f8c-4c04-wf8e-936823bf6bdb','nim' => '241511056', 'name' => 'Raihana Aisha Az-Zahra'],
            ['id' => 'bs483962-1f8c-4c05-xf8e-936823bf6bdb','nim' => '241511057', 'name' => 'Revaldi Ardhi Prasetyo'],
            ['id' => 'bs483961-1f8c-4c06-yf8e-936823bf6bdb','nim' => '241511058', 'name' => 'Revan Ramdani Permana'],
            ['id' => 'bs483967-1f8c-4c07-zf8e-936823bf6bdb','nim' => '241511059', 'name' => 'Ridho Sulistyo Saputro'],
            ['id' => 'bs483968-1f8c-4c08-ef8e-936823bf6bdb','nim' => '241511060', 'name' => 'Rifky Hermawan'],
            ['id' => 'bs483969-1f8c-4c09-rf8e-936823bf6bdb','nim' => '241511061', 'name' => 'Rina Permata Dewi'],
            ['id' => 'bs483960-1f8c-4c00-tf8e-936823bf6bdb','nim' => '241511062', 'name' => 'Salma Arifah Zahra'],
            ['id' => 'bs483961-1f8c-4c01-qf8e-936823bf6bdb','nim' => '241511063', 'name' => 'Samudra Putra Gunawan'],
            ['id' => 'bs483962-1f8c-4c02-lf8e-936823bf6bdb','nim' => '241511064', 'name' => 'Seruni Libertina Islami'],

            // D3-C
            ['id' => 'cs481968-7f8c-4c05-af8e-936823bf6ado','nim' => '241511065', 'name' => 'Ahmad Habib Muttaqin'],
            ['id' => 'cs482968-7f8c-4c05-af8e-936823bf6bdo','nim' => '241511066', 'name' => 'Alda Pujama'],
            ['id' => 'cs483968-7f8c-4c05-af8e-936823bf6cdo','nim' => '241511067', 'name' => 'Alexandrio Vega Bonito'],
            ['id' => 'cs484968-7f8c-4c05-af8e-936823bf6ddo','nim' => '241511068', 'name' => 'Andhini Widya Putri Wastika'],
            ['id' => 'cs485968-7f8c-4c05-af8e-936823bf6edo','nim' => '241511069', 'name' => 'Azkha Nazzala Prasadha Dies'],
            ['id' => 'cs486968-7f8c-4c05-af8e-936823bf6fdo','nim' => '241511070', 'name' => 'Dava Ramadhan'],
            ['id' => 'cs487968-7f8c-4c05-af8e-936823bf6gdo','nim' => '241511071', 'name' => 'Dzakir Tsabit Asy-Syafiq'],
            ['id' => 'cs488968-7f8c-4c05-af8e-936823bf6hdo','nim' => '241511072', 'name' => 'Ersya Hasby Satria'],
            ['id' => 'cs489968-7f8c-4c05-af8e-936823bf6ido','nim' => '241511073', 'name' => 'Fairuz Sheva Muhammad'],
            ['id' => 'cs481968-7f8c-4c05-af8e-936823bf6jdo','nim' => '241511074', 'name' => 'Fatimah Hawwa Alkhansa'],
            ['id' => 'cs482968-7f8c-4c05-af8e-936823bf6kdo','nim' => '241511075', 'name' => 'Gema Adzan Firdaus'],
            ['id' => 'cs483968-7f8c-4c05-af8e-936823bf6ldo','nim' => '241511076', 'name' => 'Hanifidin Ibrahim'],
            ['id' => 'cs484968-7f8c-4c05-af8e-936823bf6mdo','nim' => '241511077', 'name' => 'Helga Athifa Hidayat'],
            ['id' => 'cs485968-7f8c-4c05-af8e-936823bf6ndo','nim' => '241511078', 'name' => 'Hisyam Khaeru Umam'],
            ['id' => 'cs486968-7f8c-4c05-af8e-936823bf6odo','nim' => '241511079', 'name' => 'Ibnu Hilmi Athaillah'],
            ['id' => 'cs487968-7f8c-4c05-af8e-936823bf6pdo','nim' => '241511080', 'name' => 'Ikhsan Satriadi'],
            ['id' => 'cs488968-7f8c-4c05-af8e-936823bf6qdo','nim' => '241511081', 'name' => 'Muhamad Syahid'],
            ['id' => 'cs489968-7f8c-4c05-af8e-936823bf6rdo','nim' => '241511082', 'name' => 'Muhammad Brata Hadinata'],
            ['id' => 'cs480968-7f8c-4c05-af8e-936823bf6sdo','nim' => '241511083', 'name' => 'Muhammad Ihsan Ramadhan'],
            ['id' => 'cs481968-7f8c-4c05-af8e-936823bf6tdo','nim' => '241511084', 'name' => 'Muhammad Raihan Abubakar'],
            ['id' => 'cs482968-7f8c-4c05-af8e-936823bf6udo','nim' => '241511085', 'name' => 'Nezya Zulfa Fauziah'],
            ['id' => 'cs483968-7f8c-4c05-af8e-936823bf6vdo','nim' => '241511086', 'name' => 'Nike Kustiane'],
            ['id' => 'cs484968-7f8c-4c05-af8e-936823bf6wdo','nim' => '241511087', 'name' => 'Qlio Amanda Febriany'],
            ['id' => 'cs485968-7f8c-4c05-af8e-936823bf6xdo','nim' => '241511088', 'name' => 'Rahma Attaya Tamimah'],
            ['id' => 'cs486968-7f8c-4c05-af8e-936823bf6ydo','nim' => '241511089', 'name' => 'Rizky Satria Gunawan'],
            ['id' => 'cs487968-7f8c-4c05-af8e-936823bf6zdo','nim' => '241511090', 'name' => 'Siti Soviyyah'],
            ['id' => 'cs488968-7f8c-4c05-af8e-936823bf6ado','nim' => '241511091', 'name' => 'Varian Abidarma Syuhada'],
            ['id' => 'cs489968-7f8c-4c05-af8e-936823bf6bdo','nim' => '241511092', 'name' => 'Wyandhanu Maulidan Nugraha'],
            ['id' => 'cs480968-7f8c-4c05-af8e-936823bf6cdo','nim' => '241511093', 'name' => 'Yazid Alrasyid'],
            ['id' => 'cs484968-7f8c-4c05-af8e-736823bf6ddo','nim' => '241511094', 'name' => 'Zahra Aldila'],
            ['id' => 'cs483968-7f8c-4c05-af8e-936823bf6edo','nim' => '241511095', 'name' => 'Zakky Zhillan Muhammad Irsyad'],
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
