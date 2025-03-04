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
        $classIdC = 'be6ad5a-a8e7-4654-aa65-1a3301a394cd'; //Kelas C

        DB::table('mahasiswa')->insert([
        ]);

        $students = [
            // D3-A
            ['id' => 'aa483968-7f8c-1c05-af8e-936823bf1bda','nim' => '241511001', 'name' => 'Andi Putra Wijaya', 'class_id' => $classIdA],
            ['id' => 'aa483968-7f8c-1c05-af8e-936823bf2bdb','nim' => '241511002', 'name' => 'Arief F-Sa Wijaya', 'class_id' => $classIdA],
            ['id' => 'aa483968-7f8c-1c05-af8e-936823bf3bdc','nim' => '241511003', 'name' => 'Arnold Billy Kresnawan', 'class_id' => $classIdA],
            ['id' => 'aa483968-7f8c-2c05-af8e-936823bf4bdd','nim' => '241511004', 'name' => 'Chinta Karina Khairunissa', 'class_id' => $classIdA],
            ['id' => 'aa483968-7f8c-3c05-af8e-936823bf5bde','nim' => '241511005', 'name' => 'Christian Goklas Natanael Sitorus', 'class_id' => $classIdA],
            ['id' => 'aa483968-7f8c-3c05-af8e-936823bf6bdf','nim' => '241511006', 'name' => 'Dwika Bayu Adinata', 'class_id' => $classIdA],
            ['id' => 'aa483968-7f8c-5c05-af8e-936823bf7bdg','nim' => '241511007', 'name' => 'Emir Althaf Rasyid Kantaprawira', 'class_id' => $classIdA],
            ['id' => 'aa483968-7f8c-5c05-af8e-936823bf8bdh','nim' => '241511008', 'name' => 'Farell Daffa Aditya', 'class_id' => $classIdA],
            ['id' => 'aa483968-7f8c-6c05-af8e-936823bf9bdi','nim' => '241511009', 'name' => 'Faridha Zahiya', 'class_id' => $classIdA],
            ['id' => 'aa483968-7f8c-7c05-af8e-936823bf1bdj','nim' => '241511010', 'name' => 'Farras Fadhil Syafiq', 'class_id' => $classIdA],
            ['id' => 'aa483968-7f8c-6c05-af8e-936823bf2bdk','nim' => '241511011', 'name' => 'Fawwaz Naufal Anwar', 'class_id' => $classIdA],
            ['id' => 'aa483968-7f8c-7c05-af8e-936823bf3bdl','nim' => '241511012', 'name' => 'Gilang Aditya Sumarna', 'class_id' => $classIdA],
            ['id' => 'aa483968-7f8c-8c05-af8e-936823bf4bdm','nim' => '241511013', 'name' => 'Hilmi Mahdani Bilda', 'class_id' => $classIdA],
            ['id' => 'aa483968-7f8c-9c05-af8e-936823bf5bdn','nim' => '241511014', 'name' => 'Irvan Supriadi Situmorang', 'class_id' => $classIdA],
            ['id' => 'aa483968-7f8c-0c05-af8e-936823bf6bdo','nim' => '241511015', 'name' => 'Marrely Salsa Kasih Risky', 'class_id' => $classIdA],
            ['id' => 'aa483968-7f8c-1c05-af8e-936823bf7bdp','nim' => '241511016', 'name' => 'Maulana Ishak', 'class_id' => $classIdA],
            ['id' => 'aa483968-7f8c-2c05-af8e-936823bf8bdq','nim' => '241511017', 'name' => 'Muhammad Faliq Shiddiq Azzaki', 'class_id' => $classIdA],
            ['id' => 'aa483968-7f8c-3c05-af8e-936823bf9bdr','nim' => '241511018', 'name' => 'Muhammad Hanif', 'class_id' => $classIdA],
            ['id' => 'aa483968-7f8c-4c05-af8e-936823bf1bds','nim' => '241511019', 'name' => 'Muhammad Hasbi Hardian', 'class_id' => $classIdA],
            ['id' => 'aa483968-7f8c-5c05-af8e-936823bf2bdt','nim' => '241511020', 'name' => 'Muhammad Nabiil Fanezi', 'class_id' => $classIdA],
            ['id' => 'aa483968-7f8c-6c05-af8e-936823bf3bdu','nim' => '241511021', 'name' => 'Nabil Mudzaki Al Muharam', 'class_id' => $classIdA],
            ['id' => 'aa483968-7f8c-7c05-af8e-936823bf4bdv','nim' => '241511022', 'name' => 'Naira Tahira', 'class_id' => $classIdA],
            ['id' => 'aa483968-7f8c-8c05-af8e-936823bf5bdw','nim' => '241511023', 'name' => 'Nashwa Fathia Fasha', 'class_id' => $classIdA],
            ['id' => 'aa483968-7f8c-9c05-af8e-936823bf6bdx','nim' => '241511024', 'name' => 'Nazwa Ramadhani', 'class_id' => $classIdA],
            ['id' => 'aa483968-7f8c-1c05-af8e-936823bf7bdy','nim' => '241511025', 'name' => 'Raffi Fauzi Hermawan', 'class_id' => $classIdA],
            ['id' => 'aa483968-7f8c-9c05-af8e-936823bf8bdz','nim' => '241511026', 'name' => 'Suci Sulistiawati', 'class_id' => $classIdA],
            ['id' => 'aa483968-7f8c-8c05-af8e-936823bf9bda','nim' => '241511027', 'name' => 'Tamam Hisabulah', 'class_id' => $classIdA],
            ['id' => 'aa483968-7f8c-7c05-af8e-936823bf0bdb','nim' => '241511028', 'name' => 'Tsinan Arkan', 'class_id' => $classIdA],
            ['id' => 'aa483968-7f8c-6c05-af8e-936823bf1bdc','nim' => '241511029', 'name' => 'Wafi Ahlam Rizqulloh', 'class_id' => $classIdA],
            ['id' => 'aa483968-7f8c-5c05-af8e-936823bf2bdd','nim' => '241511030', 'name' => 'Zahwa Nazala Khalisan Herdiyana', 'class_id' => $classIdA],
            ['id' => 'aa483968-7f8c-4c05-af8e-936823bf3bde','nim' => '241511031', 'name' => 'Zainandhi Nur Fathurrohman', 'class_id' => $classIdA],
            ['id' => 'aa483968-7f8c-3c05-af8e-936823bf4bdf','nim' => '241511032', 'name' => 'Zidan Taufiqurrahman', 'class_id' => $classIdA],
            
            // D3-B
            ['id' => 'ba483969-1f8c-4c01-af8e-936823bf6bdb','nim' => '241511033', 'name' => 'Abdurahman Nur Fadilah', 'class_id' => $classIdB],
            ['id' => 'ba483968-1f8c-4c02-bf8e-936823bf6bdb','nim' => '241511034', 'name' => 'Adjie Ali Nurfizal', 'class_id' => $classIdB],
            ['id' => 'ba483967-1f8c-4c03-cf8e-936823bf6bdb','nim' => '241511035', 'name' => 'Ahmad Riyadh Almaliki', 'class_id' => $classIdB],
            ['id' => 'ba483966-1f8c-4c04-df8e-936823bf6bdb','nim' => '241511036', 'name' => 'Akmal Rezaqi Al-Farhan', 'class_id' => $classIdB],
            ['id' => 'ba483965-1f8c-4c05-ef8e-936823bf6bdb','nim' => '241511037', 'name' => 'Andreas Devan Pandu Narasamdya', 'class_id' => $classIdB],
            ['id' => 'ba483964-1f8c-4c06-ff8e-936823bf6bdb','nim' => '241511038', 'name' => 'Arman Yusuf Rifandi', 'class_id' => $classIdB],
            ['id' => 'ba483963-1f8c-4c07-gf8e-936823bf6bdb','nim' => '241511039', 'name' => 'Azzam Fadlurrahman', 'class_id' => $classIdB],
            ['id' => 'ba483962-1f8c-4c08-hf8e-936823bf6bdb','nim' => '241511040', 'name' => 'Dimas Rizal Ramadhani', 'class_id' => $classIdB],
            ['id' => 'ba483961-1f8c-4c09-if8e-936823bf6bdb','nim' => '241511041', 'name' => 'Dinanda Khayra Hutama', 'class_id' => $classIdB],
            ['id' => 'ba483960-1f8c-4c00-jf8e-936823bf6bdb','nim' => '241511042', 'name' => 'Fahraj Ananta Aulia Arkan', 'class_id' => $classIdB],
            ['id' => 'ba483969-1f8c-4c01-kf8e-936823bf6bdb','nim' => '241511043', 'name' => 'Fiandra Putera Mardani', 'class_id' => $classIdB],
            ['id' => 'ba483968-1f8c-4c02-lf8e-936823bf6bdb','nim' => '241511045', 'name' => 'Hafizh Andhika Putra', 'class_id' => $classIdB],
            ['id' => 'ba483967-1f8c-4c03-mf8e-936823bf6bdb','nim' => '241511046', 'name' => 'Idham Khalid', 'class_id' => $classIdB],
            ['id' => 'ba483966-1f8c-4c04-nf8e-936823bf6bdb','nim' => '241511047', 'name' => 'Mahesa Fazrie Mahardhika Gunadi', 'class_id' => $classIdB],
            ['id' => 'ba483965-1f8c-4c05-of8e-936823bf6bdb','nim' => '241511048', 'name' => 'Micky Ridho Pratama', 'class_id' => $classIdB],
            ['id' => 'ba483964-1f8c-4c06-pf8e-936823bf6bdb','nim' => '241511049', 'name' => 'Mohammad Jibril Fathi Farhan Al Ghifari', 'class_id' => $classIdB],
            ['id' => 'ba483963-1f8c-4c07-qf8e-936823bf6bdb','nim' => '241511050', 'name' => 'Muhamad Raditya Novandrian', 'class_id' => $classIdB],
            ['id' => 'ba483962-1f8c-4c08-rf8e-936823bf6bdb','nim' => '241511051', 'name' => 'Muhammad Faiz Ramdhani', 'class_id' => $classIdB],
            ['id' => 'ba483961-1f8c-4c00-sf8e-936823bf6bdb','nim' => '241511052', 'name' => 'Muhammad Naufal Alfarizky', 'class_id' => $classIdB],
            ['id' => 'ba483966-1f8c-4c01-tf8e-936823bf6bdb','nim' => '241511053', 'name' => 'Muhammad Naufal Nurmaryadi', 'class_id' => $classIdB],
            ['id' => 'ba483965-1f8c-4c02-uf8e-936823bf6bdb','nim' => '241511054', 'name' => 'Nauval Hilmy Firjatullah', 'class_id' => $classIdB],
            ['id' => 'ba483964-1f8c-4c03-vf8e-936823bf6bdb','nim' => '241511055', 'name' => 'Nazriel Ramdhani', 'class_id' => $classIdB],
            ['id' => 'ba483963-1f8c-4c04-wf8e-936823bf6bdb','nim' => '241511056', 'name' => 'Raihana Aisha Az-Zahra', 'class_id' => $classIdB],
            ['id' => 'ba483962-1f8c-4c05-xf8e-936823bf6bdb','nim' => '241511057', 'name' => 'Revaldi Ardhi Prasetyo', 'class_id' => $classIdB],
            ['id' => 'ba483961-1f8c-4c06-yf8e-936823bf6bdb','nim' => '241511058', 'name' => 'Revan Ramdani Permana', 'class_id' => $classIdB],
            ['id' => 'ba483967-1f8c-4c07-zf8e-936823bf6bdb','nim' => '241511059', 'name' => 'Ridho Sulistyo Saputro', 'class_id' => $classIdB],
            ['id' => 'ba483968-1f8c-4c08-ef8e-936823bf6bdb','nim' => '241511060', 'name' => 'Rifky Hermawan', 'class_id' => $classIdB],
            ['id' => 'ba483969-1f8c-4c09-rf8e-936823bf6bdb','nim' => '241511061', 'name' => 'Rina Permata Dewi', 'class_id' => $classIdB],
            ['id' => 'ba483960-1f8c-4c00-tf8e-936823bf6bdb','nim' => '241511062', 'name' => 'Salma Arifah Zahra', 'class_id' => $classIdB],
            ['id' => 'ba483961-1f8c-4c01-qf8e-936823bf6bdb','nim' => '241511063', 'name' => 'Samudra Putra Gunawan', 'class_id' => $classIdB],
            ['id' => 'ba483962-1f8c-4c02-lf8e-936823bf6bdb','nim' => '241511064', 'name' => 'Seruni Libertina Islami', 'class_id' => $classIdB],

            // D3-C
            ['id' => 'ca481968-7f8c-4c05-af8e-936823bf6ado','nim' => '241511065', 'name' => 'Ahmad Habib Muttaqin', 'class_id' => $classIdC],
            ['id' => 'ca482968-7f8c-4c05-af8e-936823bf6bdo','nim' => '241511066', 'name' => 'Alda Pujama', 'class_id' => $classIdC],
            ['id' => 'ca483968-7f8c-4c05-af8e-936823bf6cdo','nim' => '241511067', 'name' => 'Alexandrio Vega Bonito', 'class_id' => $classIdC],
            ['id' => 'ca484968-7f8c-4c05-af8e-936823bf6ddo','nim' => '241511068', 'name' => 'Andhini Widya Putri Wastika', 'class_id' => $classIdC],
            ['id' => 'ca485968-7f8c-4c05-af8e-936823bf6edo','nim' => '241511069', 'name' => 'Azkha Nazzala Prasadha Dies', 'class_id' => $classIdC],
            ['id' => 'ca486968-7f8c-4c05-af8e-936823bf6fdo','nim' => '241511070', 'name' => 'Dava Ramadhan', 'class_id' => $classIdC],
            ['id' => 'ca487968-7f8c-4c05-af8e-936823bf6gdo','nim' => '241511071', 'name' => 'Dzakir Tsabit Asy-Syafiq', 'class_id' => $classIdC],
            ['id' => 'ca488968-7f8c-4c05-af8e-936823bf6hdo','nim' => '241511072', 'name' => 'Ersya Hasby Satria', 'class_id' => $classIdC],
            ['id' => 'ca489968-7f8c-4c05-af8e-936823bf6ido','nim' => '241511073', 'name' => 'Fairuz Sheva Muhammad', 'class_id' => $classIdC],
            ['id' => 'ca481968-7f8c-4c05-af8e-936823bf6jdo','nim' => '241511074', 'name' => 'Fatimah Hawwa Alkhansa', 'class_id' => $classIdC],
            ['id' => 'ca482968-7f8c-4c05-af8e-936823bf6kdo','nim' => '241511075', 'name' => 'Gema Adzan Firdaus', 'class_id' => $classIdC],
            ['id' => 'ca483968-7f8c-4c05-af8e-936823bf6ldo','nim' => '241511076', 'name' => 'Hanifidin Ibrahim', 'class_id' => $classIdC],
            ['id' => 'ca484968-7f8c-4c05-af8e-936823bf6mdo','nim' => '241511077', 'name' => 'Helga Athifa Hidayat', 'class_id' => $classIdC],
            ['id' => 'ca485968-7f8c-4c05-af8e-936823bf6ndo','nim' => '241511078', 'name' => 'Hisyam Khaeru Umam', 'class_id' => $classIdC],
            ['id' => 'ca486968-7f8c-4c05-af8e-936823bf6odo','nim' => '241511079', 'name' => 'Ibnu Hilmi Athaillah', 'class_id' => $classIdC],
            ['id' => 'ca487968-7f8c-4c05-af8e-936823bf6pdo','nim' => '241511080', 'name' => 'Ikhsan Satriadi', 'class_id' => $classIdC],
            ['id' => 'ca488968-7f8c-4c05-af8e-936823bf6qdo','nim' => '241511081', 'name' => 'Muhamad Syahid', 'class_id' => $classIdC],
            ['id' => 'ca489968-7f8c-4c05-af8e-936823bf6rdo','nim' => '241511082', 'name' => 'Muhammad Brata Hadinata', 'class_id' => $classIdC],
            ['id' => 'ca480968-7f8c-4c05-af8e-936823bf6sdo','nim' => '241511083', 'name' => 'Muhammad Ihsan Ramadhan', 'class_id' => $classIdC],
            ['id' => 'ca481968-7f8c-4c05-af8e-936823bf6tdo','nim' => '241511084', 'name' => 'Muhammad Raihan Abubakar', 'class_id' => $classIdC],
            ['id' => 'ca482968-7f8c-4c05-af8e-936823bf6udo','nim' => '241511085', 'name' => 'Nezya Zulfa Fauziah', 'class_id' => $classIdC],
            ['id' => 'ca483968-7f8c-4c05-af8e-936823bf6vdo','nim' => '241511086', 'name' => 'Nike Kustiane', 'class_id' => $classIdC],
            ['id' => 'ca484968-7f8c-4c05-af8e-936823bf6wdo','nim' => '241511087', 'name' => 'Qlio Amanda Febriany', 'class_id' => $classIdC],
            ['id' => 'ca485968-7f8c-4c05-af8e-936823bf6xdo','nim' => '241511088', 'name' => 'Rahma Attaya Tamimah', 'class_id' => $classIdC],
            ['id' => 'ca486968-7f8c-4c05-af8e-936823bf6ydo','nim' => '241511089', 'name' => 'Rizky Satria Gunawan', 'class_id' => $classIdC],
            ['id' => 'ca487968-7f8c-4c05-af8e-936823bf6zdo','nim' => '241511090', 'name' => 'Siti Soviyyah', 'class_id' => $classIdC],
            ['id' => 'ca488968-7f8c-4c05-af8e-936823bf6ado','nim' => '241511091', 'name' => 'Varian Abidarma Syuhada', 'class_id' => $classIdC],
            ['id' => 'ca489968-7f8c-4c05-af8e-936823bf6bdo','nim' => '241511092', 'name' => 'Wyandhanu Maulidan Nugraha', 'class_id' => $classIdC],
            ['id' => 'ca480968-7f8c-4c05-af8e-936823bf6cdo','nim' => '241511093', 'name' => 'Yazid Alrasyid', 'class_id' => $classIdC],
            ['id' => 'ca484968-7f8c-4c05-af8e-736823bf6ddo','nim' => '241511094', 'name' => 'Zahra Aldila', 'class_id' => $classIdC],
            ['id' => 'ca483968-7f8c-4c05-af8e-936823bf6edo','nim' => '241511095', 'name' => 'Zakky Zhillan Muhammad Irsyad', 'class_id' => $classIdC],
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
