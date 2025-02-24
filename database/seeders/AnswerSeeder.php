<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AnswerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $now = Carbon::now();

        $answers = [
            [
                'mahasiswa_id' => 'aa483968-7f8c-2c05-af8e-936823bf4bdd', // CHINTA
                'dosen_id' => 'ra483968-7f8c-4c01-af8a-936823bf6bda',
                'status' => 'submitted',
                'answers' => [
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440001', 'score' => 5, 'answer' => 'Dalam mencari data lowongan kerja di Twitter, saya mengalami kemudahan dikeranakan saya menemukan akun khusus untuk mencari lowongan kerja IT, untuk Facebook saya mengalami kemudahan dikarenakan dalam Facebook terdapat fitur komunitas khusus untuk mencari atau membagikan lowongan kerja IT, sedangkan dalam LinkedIn saya juga mengalami kemudahan dikarenakan dalam LinkedIn saya hanya perlu mencari keyword pekerjaan yang diinginkan.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440002', 'score' => 4, 'answer' => 'Dalam data lowongan kerja yang saya dapatkan saya seringkali menemukan data yang kurang sehingga menurut saya data tersebut kurang efektif untuk digunakan'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440003', 'score' => 5, 'answer' => 'Dalam kelompok saya, struktur data yang disetujui merupakan struktur data yang seringkali ditemukan di mayoritas data sehingga sayapun puas dengan struktur tersebut'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440004', 'score' => 4, 'answer' => 'Saya seringkali memasukkan data di barisan yang salah, namun untungnya saya memeriksa kembali data yang ada dan menuliskan ulang data ke barisan yang benar'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440005', 'score' => 4, 'answer' => 'Berdiskusi dengan ketua kelompok lain bahkan diluar jam projek untuk menstandarisasi struktur data. Masalahnya adalah terkadang saat diskusi kita sering terjadi off topic.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440006', 'score' => 5, 'answer' => 'Saya selalu berkomunikasi setiap sebelum dan sesudah meeting. Saya juga memonitor anggota tim saya dan menanyakan apa saja yang sudah dilakukan setelah beberapa saat.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440007', 'score' => 4, 'answer' => 'Saat pembuatan rencana, harus ada komunikasi dan timeline penyelesaian tugas. Itu saya komunikasikan ke setiap anggota agar semuanya paham mengenai apa tujuan pada setiap waktunya.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440008', 'score' => 5, 'answer' => 'Saat menggabungkan data antara sheet anggota, saya berinisiatif menggunakan macro untuk mengotomatisasi penggabungan data antar sheet.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440009', 'score' => 5, 'answer' => 'Saat ada tugas dengan deadline dalam kurun waktu yang tidak lama, selesaikan itu terlebih dahulu. Jika ada tugas dengan deadline lama namun dengan tugas yang sulit, maka tugas tersebut harus dicicil.'],
                ],
            ],
            [
                'mahasiswa_id' => 'aa483968-7f8c-1c05-af8e-936823bf1bda', //ANDI
                'dosen_id' => 'ra483968-7f8c-4c01-af8a-936823bf6bda',
                'status' => 'submitted',
                'answers' => [
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440001', 'score' => 4, 'answer' => 'Kesulitan dalam melakukan multitasking antara spreadsheet dengan media sosial lowongan pekerjaan (LinkedIn dan Instagram).'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440002', 'score' => 4, 'answer' => 'Lihat isi poster, lalu melakukan teknik "scanning" sebanyak 3x dalam iklan lowongan tersebut, setiap scanning fokus dalam mencari keyword yang berhubungan kolom yang belum terpenuhi.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440003', 'score' => 4, 'answer' => 'Kita setuju untuk membuat struktur data seperti itu agar mudah dianalisis, dan kami rasa itu diperlukan.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440004', 'score' => 4, 'answer' => 'Hampir tidak ada free text pada struktur data kami. Sehingga tidak banyak ruang kesalahan dalam pengetikan.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440005', 'score' => 5, 'answer' => 'Berdiskusi dengan ketua kelompok lain bahkan di luar jam proyek untuk menstandarisasi struktur data. Masalahnya adalah terkadang saat diskusi kita sering terjadi off topic.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440006', 'score' => 5, 'answer' => 'Saya selalu berkomunikasi setiap sebelum dan sesudah meeting. Saya juga memonitor anggota tim saya dan menanyakan apa saja yang sudah dilakukan setelah beberapa saat.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440007', 'score' => 5, 'answer' => 'Saat pembuatan rencana, harus ada komunikasi dan timeline penyelesaian tugas. Itu saya komunikasikan ke setiap anggota agar semuanya paham mengenai apa tujuan pada setiap waktunya.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440008', 'score' => 5, 'answer' => 'Saat menggabungkan data antara sheet anggota, saya berinisiatif menggunakan macro untuk mengotomatisasi penggabungan data antar sheet.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440009', 'score' => 5, 'answer' => 'Saat ada tugas dengan deadline dalam kurun waktu yang tidak lama, selesaikan itu terlebih dahulu. Jika ada tugas dengan deadline lama namun dengan tugas yang sulit, maka tugas tersebut harus dicicil.'],
                ],
            ],
            [
                'mahasiswa_id' => 'aa483968-7f8c-3c05-af8e-936823bf6bdf', // DWIKA
                'dosen_id' => 'ra483968-7f8c-4c01-af8a-936823bf6bda',
                'status' => 'submitted',
                'answers' => [
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440001', 'score' => 4, 'answer' => 'Terdapat data duplikat, masalah jaringan internet. Saya mengumpulkan data di aplikasi Instagram jadi saya menggunakan hastag untuk memudahkan pencarian'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440002', 'score' => 5, 'answer' => 'Iklan lowongan pekerjaan sangat mudah untuk dipahami karena iklan-iklan tersebut menggunakan poin-poin daripada menggunakan teks panjang'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440003', 'score' => 4, 'answer' => 'Struktur data yang dipilih sesuai dengan data yang ada pada iklan lowongan pekerjaan yang saya cari. Namun, ada beberapa kolom struktur data yang memiliki data kosong yang banyak, yaitu kolom gender.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440004', 'score' => 5, 'answer' => 'Saya memastikan kesesuaian data sebanyak 2 kali sebelum akhirnya saya inputkan. Saya juga mencari nama perusahaan yang bersangkutan di Google untuk mengkonfirmasi bahwa benar adanya perusahaan tersebut.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440005', 'score' => 2, 'answer' => 'Saya tidak terlalu berkontribusi dalam penggabungan data sekelas, saya hanya mengusulkan beberapa pendapat melalui ketua kelompok saya. Saya juga menulis hasil dari diskusi antar ketua kelompok untuk digunakan di pertemuan selanjutnya.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440006', 'score' => 3, 'answer' => 'Saya kurang pandai dalam berkomunikasi secara lisan, jadi selama proyek berlangsung, saya hanya mengutarakan sedikit pendapat saya. Saya dapat mengikuti proses diskusi dengan baik dan dapat mengerjakan tugas sesuai dengan hasil diskusi pada setiap pertemuan.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440007', 'score' => 3, 'answer' => 'Saya hanya menyampaikan pendapat saya ketika ada kesalahan atau ketidaksesuaian pada file spreadsheet, saya juga menyampaikan beberapa ide yang kemudian diterapkan oleh kelompok saya.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440008', 'score' => 3, 'answer' => 'Masalah yang terjadi selama proyek berlangsung saya selesaikan dengan bantuan teman-teman kelompok saya. Tapi, ada beberapa masalah yang bisa saya selesaikan sendiri, contohnya adalah ketika ada data duplikat. Hal yang saya lakukan untuk menangani masalah tersebut adalah dengan menghapus salah satu data duplikat tersebut dan menghilangkan sumber link dari data tersebut supaya data duplikat tersebut tidak akan muncul lagi.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440009', 'score' => 5, 'answer' => 'Saya selalu menyelesaikan tugas yang saya terima 1 atau 2 hari sebelum tenggat waktunya. Jadi, saya bisa membantu menyelesaikan beberapa hal yang sekiranya belum selesai.'],
                ],
            ],
            [
                'mahasiswa_id' => 'aa483968-7f8c-1c05-af8e-936823bf2bdb', // ARIEF
                'dosen_id' => 'ra483968-7f8c-4c01-af8a-936823bf6bda',
                'status' => 'submitted',
                'answers' => [
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440001', 'score' => 4, 'answer' => 'Mengumpulkan iklan di media sosial cenderung mudah di platform seperti X/Twitter, Facebook, Instagram dan LinkedIn. Sedangkan ada satu platform yang kontennya dikhususkan untuk video pendek yakni TikTok, saya kesulitan mencari iklan yang sesuai kriteria karna banyaknya konten iklan lowongan pekerjaan yang sesuai kriteria tak sebanyak di platform lain.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440002', 'score' => 5, 'answer' => 'Saya menganalisis isi lowongan pekerjaan (pada platform TikTok) dengan cara menyimak video dengan seksama dan langsung mencatatnya pada spreadsheet yang telah dibuat sebelumnya'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440003', 'score' => 5, 'answer' => 'Kelompok kami sepakat menetapkan beberapa parameter penting dalam pengumpulan data lowongan pekerjaan, termasuk link sumber, nama perusahaan, minimal pendidikan, jenis kelamin, lokasi, dan posisi pekerjaan, untuk memberikan gambaran komprehensif mengenai setiap lowongan dan membantu calon pelamar menilai kelayakan mereka serta memudahkan verifikasi informasi.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440004', 'score' => 4, 'answer' => 'Saya mulai dengan menonton atau membaca iklan. Kemudian saya mengisikannya pada field yang tersedia. Setelah itu saya cek lagi atau membaca ulang apa yang telah saya inputkan dengan apa yang saya dapat/baca.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440005', 'score' => 4, 'answer' => 'Peran saya dalam menggabungkan data dengan kelompok lain cukup berarti. Saya pernah mengusulkan menggunakan penggabungan menggunakan formula =ARRAY kemudian menyortirnya walaupun akhirnya tak digunakan karena teman kelompok saya menemukan cara yang lebih efektif.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440006', 'score' => 5, 'answer' => 'Saat bekerja bersama tim, saya cenderung mengungkapkan apa yang saya pikirkan baik itu berupa ide, argumentasi dan sebagainya. Walaupun demikian, saya menghargai pendapat orang lain dan tak memaksakan apa yang saya mau atau ide yang saya punya untuk digunakan. Dengan demikian saya dapat berkomunikasi dan berkolaborasi dengan kelompok tanpa adanya kendala.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440007', 'score' => 5, 'answer' => 'Saya berkomunikasi dengan kelompok ketika ada suatu permasalahan atau perlu membuat keputusan seperti berdiskusi tentang struktur data dan sebagainya. Tak jarang saya juga bertanya jika dirasa ada yang kurang saya pahami ke anggota lain. Selain itu saya sering menanyakan progres, kesusahan, menawarkan bantuan dan sebagainya.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440008', 'score' => 5, 'answer' => 'Saat mengolah data dari TikTok, saya kesulitan mencari data yang lengkap. Oleh karena itu saya bertanya dan berdiskusi dengan kelompok bagaimana mengatasi masalah ini. Setelah berdiskusi kami menentukan untuk menggunakan data dari platform TikTok seadanya saja.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440009', 'score' => 4, 'answer' => 'Saya membuat skala prioritas, baik secara tertulis maupun hanya direncanakan di kepala. Dalam kasus khusus, ketika menghadapi banyak tugas atau tenggat waktu, menyusun skala prioritas menjadi suatu keharusan bagi saya untuk menyelesaikan tugas-tugas tersebut dengan efektif.'],
                ],
            ],
            [
                'mahasiswa_id' => 'aa483968-7f8c-3c05-af8e-936823bf5bde', // CHRISTIAN
                'dosen_id' => 'ra483968-7f8c-4c01-af8a-936823bf6bda',
                'status' => 'submitted',
                'answers' => [
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440001', 'score' => 4, 'answer' => 'Saya menghadapi sebuah kesulitan, karena ini adalah pertama kali saya mencari loker di media sosial, jadi pada saat mengumpulkan data dan mengekstrak data, pengerjaan saya lebih lambat dari teman- teman kelompok saya. Untuk kemudahan, saya mendapatkan kemudahan setelah mengerjakan tugas saya selama 3 minggu karena saya merasa sudah terbiasa dengan tugas-tugas saya.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440002', 'score' => 4, 'answer' => 'Saya menganalisis dari bagian pekerjaannya dulu karena ditugaskan untuk mencari loker IT. Lalu saya melihat tanggal poster loker tersebut diupload karena ditugaskan untuk mencari loker IT dari Agustus 2023 sampai yang terbaru. Setelah itu saya melihat nama perusahaan, lokasi, umur maksimal, umur minimal, contact person, dan data-data yang harus diekstrak sesuai dengan yang disepakati kelompok saya.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440003', 'score' => 4, 'answer' => 'Disini saya puas dengan struktur data yang kelompok saya usulkan, karena struktur data kelompok saya sudah lumayan lengkap untuk data sebuah lowongan kerja.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440004', 'score' => 3, 'answer' => 'Disini saya terkadang suka tidak teliti karena ini adalah pengalaman pertama saya untuk mengumpulkan data lowongan kerja sebanyak ini dan terkadang saya juga sering salah menginput data kedalam Excel.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440005', 'score' => 3, 'answer' => 'Disini saya hanya sedikit berkontribusi dalam penggabungan data dan struktur dari kelompok lain karena hanya perwakilan saja yang berunding untuk penggabungan data dan struktur data dari kelompok lain.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440006', 'score' => 5, 'answer' => 'Dalam proyek berlangsung, komunikasi dan kolaborasi dengan anggota kelompok berjalan dengan lancar meskipun pada awal proyek terdapat miskomunikasi tetapi hal tersebut dengan cepat di-backup oleh kelompok kami. Pada saat saya merasa kesulitan pada saat mengekstrak data, teman-teman sekelompok saya langsung memberikan ide atau saran yang membuat pekerjaan tugas kelompok kami terasa sangat cepat.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440007', 'score' => 2, 'answer' => 'Selama 7 minggu saya belajar proyek, saya masih sedikit dalam menyampaikan ide dan solusi kepada anggota kelompok karena ini adalah pengalaman pertama saya dalam bidang IT.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440008', 'score' => 4, 'answer' => 'Pada saat ada lowongan kerja yang datanya kurang dengan data yang sudah ditentukan oleh kelompok kami, saya langsung mencari data yang lain agar tidak menghambur waktu dan agar membuat pekerjaan lebih efisien dan cepat.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440009', 'score' => 4, 'answer' => 'Disini saya rasa sudah cukup baik, contohnya seperti pada saat pengumpulan logbook atau pengumpulan data yang ditentukan pada hari pengerjaan, saya langsung memprioritaskan pekerjaan tersebut karena saya adalah orang yang malas mengerjakan sesuatu di rumah, jadi pada saat pengumpulan saya hanya tinggal memberikan hasil kerja saya.']
                ]
            ],
            [
                'mahasiswa_id' => 'aa483968-7f8c-6c05-af8e-936823bf2bdk', // FAWWAZ
                'dosen_id' => 'ra483968-7f8c-4c02-af8b-936823bf6bdi',
                'status' => 'submitted',
                'answers' => [
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440001', 'score' => 4, 'answer' => 'Saya lumayan kemudahan ketika mencari lowongan pekerjaan'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440002', 'score' => 4, 'answer' => 'Iklan tersebut berisi lowongan pekerjaan yang berisikan persyaratan persyaratan sehingga. Tetapi, di dalam iklan tersebut terdapat informasi yang masih belum jelas, yang akan membuat pencari lowongan pekerjaan kebingungan.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440003', 'score' => 5, 'answer' => 'Struktur data kelompok kami, menurut pendapat saya sendiri(subjektif), para pencari lowongan pekerjaan akan kemudahan karena struktur datanya mungkin sesuai dengan apa yang pencari lowongan kerja cari.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440004', 'score' => 1, 'answer' => 'Saya masih belum dapat memastikan keakuratan data yang saya ambil. Masih belum ada solusi terkait hal ini.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440005', 'score' => 5, 'answer' => 'Peran saya adalah mengoordinasi semua kelompok (1 kelas) dan menanyakan struktur data kelompok masing masing dan dibuat tabel untuk melihan mana yang ada dan tidak ada, dan mana yang semua kelompok struktur datanya ada, sehingga setiap yang setiap kelompok ada yang struktur datanya sama, maka itu akan diambil untuk digabungkan. Sisanya di diskusikan oleh ketua kelompoknya memilih mana struktur data yang hilang dan diambil.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440006', 'score' => 2, 'answer' => 'Saya hanya mendengar arahan dari ketua kelompok, jarang memberikan saran maupun berdiskusi.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440007', 'score' => 3, 'answer' => 'Paling menanyakan "kamu bagian sosmed apa?", dan menanyakan struktur datanya seperti apa.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440008', 'score' => 3, 'answer' => 'Berdiskusi dengan anggota kelompok untuk mencari solusi yang lebih baik.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440009', 'score' => 4, 'answer' => 'Contohnya ketika saya memiliki 3 deadline tugas, saya harus melihat dari kapan tugas tersebut dikumpulkan dan juga tak luput melihat dari tingkat kesulitan tersebut. Jika soalnya mudah dan deadline paling lama, maka saya akan mengerjakannya terakhiran. Jika soalnya sulit dan deadlinenya dekat, maka saya akan cepat-cepat menyelesaikan tugas tersebut agar ke tugas lain tidak menghambat.'],
                ],
            ],
            [
                'mahasiswa_id' => 'aa483968-7f8c-7c05-af8e-936823bf1bdj', // FARRAS
                'dosen_id' => 'ra483968-7f8c-4c02-af8b-936823bf6bdi',
                'status' => 'submitted',
                'answers' => [
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440001', 'score' => 5, 'answer' => 'Saya merasa sangat mudah dalam mengumpulkan iklan lowongan pekerjaan dikarnakan saya mendapatkan bagian tugas untuk mengumpulkan iklan lowongan pekerjaan di media sosial LinkedIn...'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440002', 'score' => 5, 'answer' => 'Saya menganalisisi iklan lowongan pekerjaan dengan cara dari judul lowongan pekerjaan, nama perusahaan, lokasi pekerjaan, deskripsi pekerjaan...'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440003', 'score' => 5, 'answer' => 'Saya puas karena struktur data yang dibuat oleh saya bersama kelompok saya semuanya telah baik dan memiliki tujuannya...'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440004', 'score' => 4, 'answer' => 'Saya memastikan keakuratan data yang saya input itu pasti akurat karena saya selalu melakukan cross-check sebelum lanjut menganalisis...'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440005', 'score' => 5, 'answer' => 'Proses penggabungan data dengan kelompok lain di kelas sangat seru, meski mengalami beberapa masalah seperti perbedaan kolom...'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440006', 'score' => 5, 'answer' => 'Kerja sama antara kelompok saya sangat baik karena kelompok saya selalu mengadakan rapat pagi atau koordinasi pagi...'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440007', 'score' => 5, 'answer' => 'Kerja sama antara kelompok lain juga sangat baik karena pada saat penggabungan data untuk satu kelas kami seluruh kelas mengadakan rapat besar...'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440008', 'score' => 5, 'answer' => 'Saya menghadapi masalah atau mengidentifikasi akan terjadinya masalah dengan tidak terburu-buru dan diselesaikan seiring berjalannya waktu...'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440009', 'score' => 5, 'answer' => 'Sangat baik, saya selalu memprioritaskan menyelesaikan tugas saya di kelompok hanya pada saat mata kuliah proyek 1...'],
                ],
            ],
            [
                'mahasiswa_id' => 'aa483968-7f8c-5c05-af8e-936823bf7bdg', // EMIR
                'dosen_id' => 'ra483968-7f8c-4c02-af8b-936823bf6bdi',
                'status' => 'submitted',
                'answers' => [
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440001', 'score' => 5, 'answer' => 'saya menghadapi kesulitan pada awal awal karena saya jarang menggunakan sosmed dan pada akhirnya saya terbiasa dengan menggunakan sosmed untuk mencari sesuatu spesifik'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440002', 'score' => 3, 'answer' => 'pertama saya mencari poster loker dulu dan saya mengscreenshot nya dan memasukan screenshotnya ke dalam onedrive dan dijadikan link agar para pelamar bisa melihat bukti nya dan saya mengisi struktur data lainnya per poster'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440003', 'score' => 4, 'answer' => 'saya lumayan puas dengan hasil struktur data yang telah di buat oleh kelompok saya karena menurut pandangan saya sebagai ketua kelompok struktur itu sudah mencakup semua data yang pelamar butuhkan tetapi kelompok saya tidak mencamtumkan gaji dan deadline terakhir untuk pekerjaan tersebut alasan tidak mencamtumkan juga karena di poster loker jarang ada gaji tetapi untuk deadline banyak hanya kelompok kami saja tidak kepikiran'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440004', 'score' => 4, 'answer' => 'saat saya melengkapi data saya membacanya dengan hati hati dan juga ada yang di cek dua kali tetapi saya kesulitan karena banyak data yang tidak memenuhi struktur data'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440005', 'score' => 3, 'answer' => 'dalam penggabungan data dengan kelompok lain saya sebagai ketua kelompok saya menjadi perwakilan untuk mengusulkan struktur data yang saya ambil tetapi saya tidak terlalu banyak mengusulkan pendapat dan juga untuk penggabungan data kelas saya belum melakukannya baru penggabungan struktur saja'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440006', 'score' => 4, 'answer' => 'saya sebagai ketua mengaping para anggota saya dan mengingatkan apabila ada deadline yang harus dikerjakan dan juga membantu anggota saya yang kesulitan walaupun semua anggota saya menolak tawaran saya'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440007', 'score' => 4, 'answer' => 'pada saat penentuan pertemuan untuk membuat struktur data dan memutuskan siapa yang mengurus platform diantara 5 yang sudah diberitahukan'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440008', 'score' => 5, 'answer' => 'saya membantu anggota kelompok saya dengan menanyakan apakah ada yang mau dibantu dengan pekerjaannya apabila tidak selesai sesuai deadline tapi tetapi saya tidak konsisten dengan menanyakan pertanyaan itu.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440009', 'score' => 3, 'answer' => 'saya tidak pernah telat mengumpulkan tugas tapi saya selalu mepet dengan deadline saya selalu mengsubmit tugas proyek ini di selalu 20 menit sebelum deadline dan juga tidak selesai saat pelajaran proyek jadi saya harus lembur.'],
                ],
            ],
            [
                'mahasiswa_id' => 'aa483968-7f8c-7c05-af8e-936823bf3bdl', // GILANG
                'dosen_id' => 'ra483968-7f8c-4c02-af8b-936823bf6bdi',
                'status' => 'submitted',
                'answers' => [
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440001', 'score' => 4, 'answer' => 'Saat pengumpulan iklan pada platform Facebook, saya dimudahkan dengan adanya grup atau komunitas pencari lowongan kerja khusus IT. Selain itu, pada grup/komunitas tersebut terdapat fitur pencarian yang memudahkan saya untuk mencari dengan lebih detail berdasarkan tahun, tanggal, dan sebagainya.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440002', 'score' => 3, 'answer' => 'Saya menganalisis lowongan kerja hanya mencari yang menurut saya penting saja, seperti umur, min. Pendidikan, gender, dan posisi. Selain itu, tambahan data lainnya hanyalah kesepakatan kelompok.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440003', 'score' => 2, 'answer' => 'Saya merasa kurang karena data yang saya usulkan hanya bagian kecil dari data yang penting. Selain itu, usulan anggota lain lebih masuk akal karena ada alasan dibalik pemilihan data tersebut sedangkan saya tidak. Pada akhirnya, seluruh anggota menggunakan struktur data yang telah disepakati ketika diskusi.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440004', 'score' => 5, 'answer' => 'Setiap saya menemukan lowongan kerja, akan saya ambil fotonya dan akan saya baca kembali. Tiap kata yang ada pada foto tersebut akan saya masukkan ke dalam excel sehingga tidak ada perubahan data.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440005', 'score' => 3, 'answer' => 'Saya mengusulkan bagian alamat kantor yang dimana sering terjadi ambigu. Terkadang, alamat kantor yang dicantumkan adalah kantor tempat pelamar kerja bukan alamat kantor pusat. Jadi, saya bertanya dan berdiskusi tentang hal itu dengan kelompok lain.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440006', 'score' => 4, 'answer' => 'Saya biasanya memastikan sudah sampai mana proses kelompok dengan cara menanyakan di grup WA kelompok atau secara langsung. Jika ada usulan/pendapat dari anggota, saya akan menerima hal tersebut dan berdiskusi dengan anggota lainnya supaya tidak ada kesalahpahaman.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440007', 'score' => 3, 'answer' => 'Biasanya, dalam masalah logbook kelompok atau beberapa data yang kosong saya harus berkomunikasi. Terkadang saat koordinasi awal, kelompok terlihat bingung apa yang harus dikerjakan hari ini. Jadi, saya inisiatif berbicara supaya semuanya jelas.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440008', 'score' => 3, 'answer' => 'Sebenernya, masih banyak hal yang saya tidak bisa tangani tanpa bantuan anggota kelompok lain. Masalah yang saya sering selesaikan adalah masalah logbook kelompok dan pengumpulan di e-learning saja.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440009', 'score' => 2, 'answer' => 'Saya menyusun prioritas pengumpulan tugas berdasarkan tenggat waktu. Jika ada tugas yang harus dikumpulkan pada tenggat waktu yang lebih dekat, maka saya akan kerjakan tugas itu terlebih dahulu.'],
                ],
            ],
            [
                'mahasiswa_id' => 'aa483968-7f8c-6c05-af8e-936823bf9bdi', // FARIDHA
                'dosen_id' => 'ra483968-7f8c-4c02-af8b-936823bf6bdi',
                'status' => 'submitted',
                'answers' => [
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440001', 'score' => 5, 'answer' => 'Terdapat sedikit kesulitan pada saat awal-awal pencarian lowongan pekerjaan pada platform TikTok, tetapi setelah saya mengeksplornya, pencarian lowongan pekerjaan menjadi lebih mudah, dan saya dapat mengumpulkan lowongan pekerjaan melebihi dari target awal.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440002', 'score' => 5, 'answer' => 'Pada saat menemukan lowongan pekerjaan saya menganalisis data pada lowongan pekerjaan dan melihat apakah terdapat aspek-aspek penting yang dibutuhkan ketika ingin melamar pekerjaan, seperti nama perusahaan, jenis pekerjaan, kontak untuk dihubungi, kapan tenggat pengiriman lamaran, dan saya juga menyamakan data pada lowongan pekerjaan dengan struktur data kelompok saya.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440003', 'score' => 5, 'answer' => 'Kelompok kami mendiskusikan terlebih dahulu untuk memutuskan struktur data apa saja yang akan dipakai dalam mendata lowongan pekerjaan, dan struktur data yang diambil sudah sesuai dengan kebutuhan kelompok.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440004', 'score' => 5, 'answer' => 'Pada saat saya menemukan postingan lowongan pekerjaan, saya menganalisis informasi yang tercantum pada lowongan pekerjaan dengan baik, dan saya tidak akan mengambil lowongan pekerjaan tersebut jika data yang saya inginkan kurang atau tidak ada.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440005', 'score' => 4, 'answer' => 'Peran saya dalam proses penggabungan data adalah untuk melengkapi struktur data yang masih kurang dan saya mencari lowongan pekerjaan melebihi target karena jika saja terjadi duplikat data, akan ada backup data untuk menggantinya.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440006', 'score' => 5, 'answer' => 'Saya mengkomunikasikan hasil kerja saya dengan anggota kelompok saya dan saya juga meminta saran kepada mereka jika terdapat kekurangan pada data yang saya kumpulkan, agar saya bisa memperbaikinya.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440007', 'score' => 4, 'answer' => 'Pada saat saya sedang menginput data, saya memiliki sedikit kebingungan tentang salah satu struktur data, saya bertanya dan mendiskusikannya bersama kelompok saya, dan setelah berdiskusi kami menyepakati keputusan yang kami ambil.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440008', 'score' => 4, 'answer' => 'Pada struktur data kelompok kami terdapat struktur alamat, pada saat saya sedang menginput data saya sedikit kebingungan, apakah alamat tersebut diisi oleh alamat perusahaan atau alamat penempatan, lalu saya bertanya dan mendiskusikannya dengan kelompok saya, dan setelah berdiskusi kami sepakat untuk memasukkan alamat penempatan pada struktur data.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440009', 'score' => 5, 'answer' => 'Pada saat sedang mencari lowongan pekerjaan, saya tahu akan terdapat kesulitan saat mencari pada platform TikTok, sehingga waktu yang dihabiskan lebih banyak pada saat mencari lowongan pekerjaan, dan setelah itu saya langsung menginputkan data ke dalam spreadsheet sambil saya mengolah lagi lowongan pekerjaan yang saya dapatkan.']
                ]
            ],
            [
                'mahasiswa_id' => 'aa483968-7f8c-5c05-af8e-936823bf8bdh', //FARREL
                'dosen_id' => 'ra483968-7f8c-4c02-af8b-936823bf6bdi',
                'status' => 'submitted',
                'answers' => [
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440001', 'score' => 4, 'answer' => 'Untuk kesulitan mengumpulkan iklan bagi saya tidak ada karena di kelompok saya untuk proses pengumpulan iklan dibagi menjadi 1 anggota 1 platform dan saya mendapatkan bagian platform Instagram yang dimana Instagram ini merupakan salah satu social media yang saya sering gunakan. sehingga saya dapat dengan mudah mencari berbagai iklan di social media yang sangat familiar bagi saya ini yaitu Instagram.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440002', 'score' => 4, 'answer' => 'Mula mula saya melihat apakah iklan tersebut sudah sesuai dengan bidang yang ditentukan atau tidak dalam hal ini yaitu bidang IT. Lalu setelah itu saya melihat kualifikasi atau syarat syarat apa saja yang dibutuhkan oleh perusahan lalu mencatat nya pada spreadsheet yang sudah kelompok kami sepakati.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440003', 'score' => 4, 'answer' => 'Struktur data yang kelompok kami tentukan diantara nama perusahaan, umur, jenis kelamin, minimal pengalaman, minimal Pendidikan, tanggal posting, posisi yang dilamar, link pendaftaran. Menurut saya struktur data yang sudah kelompok kami tentukan sudah sesuai tetapi ada satu struktur yang menurut saya penting tetapi tidak kami cantumkan dalam struktur data yaitu contact person.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440004', 'score' => 4, 'answer' => 'Kami memastikan data yang kami inputkan akurat dengan cara dengan menambahkan kolom baru untuk screenshot lowongan pekerjaan sehingga bila ada keraguan dalam input data maka, kami dapat melihat kembali screenshoot yang sudah kami lampirkan.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440005', 'score' => 4, 'answer' => 'Peran saya dalam proses penggabungan data dengan kelompok lain mungkin saya ikut berpartisipasi dalam rapat atau diskusi bagaimana kita semua dalam 1 kelas menentukan bagaimana struktur data kelas kami kedepannya.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440006', 'score' => 4, 'answer' => 'Kami biasanya berdiskusi hal hal apa saja yang harus kita lakukan dan membaginya dengan semua anggota kelompok. Setelah itu kami mengerjakan pekerjaan dan bila ada anggota kelompok yang membutuhkan bantuan atau kesusahan kami sesame anggota kelompok akan saling tolong menolong.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440007', 'score' => 4, 'answer' => 'Jika memberikan ide atau menyelesaikan masalah saya mungkin kurang berkontribusi tetapi terkadang saya mengkoreksi bagaimana hasil kerja kami dan terkadang saya juga menanyakan bagaimana jika saya dihadapkan dengan beberapa pilihan.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440008', 'score' => 4, 'answer' => 'Jika terdapat sebuah masalah saya akan mencoba menyelesaikannya sendiri terlebih dahulu lalu bila saya merasa kurang mampu dalam menghadapi masalah tersebut maka saya akan meminta bantuan kepada sesama anggota tim dengan melakukan diskusi.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440009', 'score' => 4, 'answer' => 'Di kelompok kami, kami biasanya mengadakan rapat kecil pada pagi hari untuk menentukan hal apa saja yang perlu kami lakukan selama 1 hari ini dan menentukan target apa yang harus dicapai.'],
                ],
            ],
            [
                'mahasiswa_id' => 'aa483968-7f8c-2c05-af8e-936823bf8bdq', // FALIQ
                'dosen_id' => 'ra483968-7f8c-4c03-af8c-936823bf6bdu',
                'status' => 'submitted',
                'answers' => [
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440001', 'score' => 4, 'answer' => 'Saya menghadapi kemudahan dalam mengumpulkan data dikarenakan platform yang saya gunakan memang berfungsi untuk mencari lowongan pekerjaan. Dan untuk kendala mungkin ketika saya sudah mengumpulkan data yang banyak, terdapat data yang hilang sehingga saya harus mengganti data tersebut dengan data yang lainnya. dan kendala lainnya adalah ketika saya mencari data, saya mencari data pada laman postingan sehingga itu tidak efisien menurut saya.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440002', 'score' => 4, 'answer' => 'Saya menganalisis lowongan pekerjaan melalui parameter yang ada pada kelompok saya sehingga saya hanya mencari data data yang diperlukan untuk kelompok saya. Tetapi pada proses itu saya menjadi tahu banyak hal terkait parameter yang dibutuhkan sebuah instansi atau perusahaan yang untuk mencari karyawan yang akan di rekrut sehingga itu memotivasi saya dalam belajar agar saya bisa menjadi lulusan yang mumpuni di bidang IT dan mencapai target parameter yang perusahaan itu berikan.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440003', 'score' => 5, 'answer' => 'Pada pengambilan keputusan untuk membuat struktur data yang ada, kami mencoba mencari jawaban terkait pertanyaan pertanyaan yang biasa di tanyakan oleh pencari lowongan pekerjaan. kemudian kami pun berdiskusi terkait data yang perlu diambil. dari keputusan tersebut sudah tepat dikarenakan struktur data kami memiliki banyak sekali parameter data yang biasanya ditanyakan oleh para pencari lowongan pekerjaan.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440004', 'score' => 3, 'answer' => 'Saya mungkin kurang teliti dalam proses penginputan data ke excel dikarenakan saya masih baru menggunakan aplikasi tersebut dan dengan demikian untuk menyimpan keakuratan data saya menyimpan info lowongan pekerjaan tersebut pada fitur simpan di aplikasi tersebut.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440005', 'score' => 3, 'answer' => 'Peran saya dalam pra proses penggabungan data ialah saya mengusulkan untuk para ketua kelompok berunding untuk menentukan parameter satu kelas dan juga saya sebagai scribe bertugas untuk mengamati hasil dari diskusi para ketua kelompok kami. untuk masalah yang saya hadapi ketika pra penggabungan data ialah ketidakkondusifan rapat sehingga saya sebagai scribe tidak dapat mencatat semua pembahasan yang dilakukan di rapat itu.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440006', 'score' => 5, 'answer' => 'Selama proyek berlangsung kami selalu bersama beriringan dan saling berkomunikasi dengan kelompok terkait semua yang dilakukan. baik itu pembuatan logbook, pembuatan ppt, pembagian tugas yang tepat, pembuatan dokumen laporan, dll.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440007', 'score' => 3, 'answer' => 'Sehari sebelum presentasi kelompok, kelompok kami berdiskusi terkait pencarian data bersama, tetapi dengan deadline yang sangat amat dekat dan progress pekerjaan yang belum maksimal, saya mencoba menghubungi semua rekan saya dan mencoba membagi tugas ke rekan rekan saya, pada saat itu juga terdapat deadline dari tugas yang lainnya sehingga itu membuat fokus kita pecah, tetapi dengan meyakinkan rekan rekan saya bahwa itu pasti bisa akhirnya kami pun mulai berusaha maksimal dalam menyelesaikan tugas yang ada. dan akhirnya kami pun berhasil menyelesaikan tugas tersebut.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440008', 'score' => 3, 'answer' => 'Saya menemukan suatu masalah yang terdapat pada data yang sudah saya kumpulkan yaitu terdapat data yang hilang sehingga saya pun mulai mengganti dengan data yang baru dan juga saya menambahkan jumlah data yang harus saya kumpulkan yang mula mula target dari data yang harus dikumpulkan ialah 50 data menjadi 60 data dengan 10 data tambahan yang berguna untuk back up data jika nantinya terdapat data yang hilang lagi.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440009', 'score' => 4, 'answer' => 'Ketika saya mengerjakan pembuatan ppt pada kelompok, saya menggunakan skala prioritas dimana saya melihat tugas mana yang memiliki deadline yang paling cepat dan dari segi kesulitan merupakan tugas yang sangat mudah terlebih dahulu.']
                ],
            ],
            [
                'mahasiswa_id' => 'aa483968-7f8c-1c05-af8e-936823bf7bdp', // ISHAK
                'dosen_id' => 'ra483968-7f8c-4c03-af8c-936823bf6bdu',
                'status' => 'submitted',
                'answers' => [
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440001', 'score' => 4, 'answer' => 'Kesulitan dalam mencari lowongan kerja di Facebook adalah memastikan bahwa iklan lowongan tersebut valid dan bukan penipuan. Selain itu, ada kemungkinan iklan yang sama diposting di akun atau grup yang berbeda sehingga harus selalu melakukan recheck.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440002', 'score' => 4, 'answer' => 'Saya melihat posisi pekerjaannya terlebih dahulu untuk memastikan bahwa lowongan ini adalah lowongan kerja IT. Setelah itu, saya melihat komentar postingan untuk mengetahui respon dari para pencari kerja terhadap postingan tersebut sehingga saya dapat mengetahui apakah lowongan tersebut valid atau penipuan.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440003', 'score' => 4, 'answer' => 'Struktur data yang kelompok kami putuskan adalah sebagai berikut:\n- Nama Perusahaan\n- Posisi\n- Jenjang minimal\n- Jurusan\n- Batasan umur\n- Hardskill\n- Pengalaman Kerja\n- Jenis Kelamin\n- Domisili\n- Sistem Kerja\n- Platform\n- Link sumber\nKeputusan ini adalah hasil diskusi kelompok. Kami merasa struktur data ini sudah cukup untuk lowongan kerja. Pendapat saya pribadi, struktur ini memiliki beberapa kekurangan. Salah satunya adalah tidak adanya kontak seperti email atau nomor telepon.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440004', 'score' => 3, 'answer' => 'Untuk memastikan data yang diinputkan akurat, saya melakukan analisis terlebih dahulu terhadap persyaratan yang tercantum di iklan lowongan pekerjaan. Setelah itu, saya menginputkan data satu persatu sesuai dengan yang tercantum di iklan lowongan. Namun, masih ada beberapa data yang kurang akurat seperti pada pengalaman ataupun hardskill. Dimana pada hardskill atau pengalaman itu sering kali ada beberapa yang tidak dijelaskan dengan akurat karena kesulitan dalam memahaminya.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440005', 'score' => 3, 'answer' => 'Saya tidak terlalu banyak berkontribusi, saya hanya memberikan beberapa saran dan pendapat dalam forum diskusi. Namun, saya tidak memberikan sesuatu yang dapat dipertimbangkan atau berpengaruh.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440006', 'score' => 4, 'answer' => 'Saya selalu melakukan konfirmasi dan bertanya ketika melakukan suatu pekerjaan agar apa yang dikerjakan itu benar dan tidak terjadi kesalahpahaman. Selain itu, saya juga cukup aktif dalam melakukan diskusi untuk membuat keputusan.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440007', 'score' => 4, 'answer' => 'Ketika persiapan presentasi, kelompok kami memerlukan diskusi terkait bagaimana dan apa saja yang akan disampaikan. Dalam diskusi ini saya dan rekan saya aktif dalam berkomunikasi. Saya juga berkontribusi menyampaikan beberapa gagasan dan temuan mengenai data kelompok lain. Sehingga, kelompok kami dapat melakukan presentasi dengan cukup baik walau tetap ada kekurangannya.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440008', 'score' => 4, 'answer' => 'Tantangan teknis: Sebelum struktur data yang dipilih sekarang, pendidikan minimal hanya dibuat dalam satu kolom. Namun, setelah diskusi kami memutuskan untuk membuatnya menjadi dua kolom dan membuatnya menjadi dropdown. Kelompok kami awalnya tidak mengerti membuat dropdown, namun saya melakukan eksplorasi dan menemukan caranya, dan saya langsung mengimplementasikannya di spreadsheet milik kelompok kami.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440009', 'score' => 3, 'answer' => 'Saya dapat dibilang terlalu santai dalam mengerjakan, dimana target yang dicapai sedikit terlambat dibandingkan kelompok lain di kelas saya. Dalam mengatasi ini, saya melakukan lebih banyak pekerjaan dari sebelumnya, serta memprioritaskan kerja proyek ini dibanding pekerjaan lain yang dapat dilakukan di lain waktu.']
                ]
            ],
            [
                'mahasiswa_id' => 'aa483968-7f8c-9c05-af8e-936823bf5bdn', // IRFAN
                'dosen_id' => 'ra483968-7f8c-4c03-af8c-936823bf6bdu',
                'status' => 'submitted',
                'answers' => [
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440001', 'score' => 5, 'answer' => 'Saya mencari lowongan kerja melalui platform TikTok, dan awalnya saya merasa kesulitan dikarenakan dalam platform TikTok hanya sedikit informasi mengenai lowongan pekerjaan yang terlihat di beranda maupun melalui pencarian. Maka dari itu saya mencoba memasukkan keyword yang spesifik dalam melakukan pencarian lowongan kerja. Kemudian saya mencoba mencari akun-akun TikTok yang menyediakan/memposting berbagai lowongan pekerjaan sehingga akun-akun tersebut dapat mempermudah saya dalam mencari lowongan pekerjaan di TikTok.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440002', 'score' => 4, 'answer' => 'Pertama-tama saya akan memastikan apakah rentang tanggal postingannya masih sesuai dengan apa yang disampaikan oleh bapak/ibu manager. Setelah itu saya akan melihat apakah lowongan pekerjaan tersebut termasuk lowongan pekerjaan di bidang IT atau tidak. Kemudian saya akan mulai mengambil data-data lowongan pekerjaan tersebut yang dimulai dari: Nama Perusahaan, Posisi atau Bidang Pekerjaan yang diambil, minimal pendidikan, hard skill, dan pengalaman kerja. Kemudian saya bersama kelompok akan berdiskusi mengenai tambahan data-data lain apakah akan ikut dimasukkan ke dalam proyek atau tidak.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440003', 'score' => 5, 'answer' => 'Kami melakukan perundingan untuk pencarian struktur data yang akan kami buat, di mana akhirnya kami sepakat untuk membuat struktur data yang terdiri dari:
                        1. Nama Perusahaan
                        2. Posisi Pekerjaan
                        3. Persyaratan:
                            - Jenjang Minimal
                            - Jurusan
                            - Batasan Umur
                            - Hard Skill
                            - Pengalaman Kerja
                            - Jenis Kelamin
                        4. Domisili
                        5. Sistem Kerja
                        6. Platform
                        7. Link Sumber.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440004', 'score' => 4, 'answer' => 'Saya melakukan pengecekan kembali setelah saya menginputkan data ke Excel.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440005', 'score' => 4, 'answer' => 'Untuk penggabungan, kami masih hanya mengambil file data dari kelompok lain dan memasukkannya ke file Excel kami. Data kelompok kami dengan data kelompok lain ada dalam satu file tetapi masih terpisah.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440006', 'score' => 4, 'answer' => 'Saya berkomunikasi dengan anggota kelompok, yaitu dengan memberikan saran atau menambahkan sesuatu yang kurang dalam diskusi kelompok.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440007', 'score' => 3, 'answer' => 'Situasi ketika saya mendapat data yang kurang sesuai strukturnya dengan data tim saya sehingga saya membuat keputusan untuk memutuskan apa yang akan saya dan tim saya lakukan dalam membuat datanya.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440008', 'score' => 4, 'answer' => 'Masalah kolaboratif yaitu terjadinya miskomunikasi sehingga saya harus mengonfirmasi ulang dengan kelompok saya.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440009', 'score' => 4, 'answer' => 'Jika saya merasa waktu saya kosong, kadang saya bisa melakukan pekerjaan saya di sore hari, namun terkadang juga saya mengerjakan pekerjaan saya pada malam hari.'],
                ],
            ],
            [
                'mahasiswa_id' => 'aa483968-7f8c-0c05-af8e-936823bf6bdo', // MARRELY
                'dosen_id' => 'ra483968-7f8c-4c03-af8c-936823bf6bdu',
                'status' => 'submitted',
                'answers' => [
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440001', 'score' => 5, 'answer' => 'Ya, saya mengalami kendala dimana beberapa link dari file saya tidak dapat terakses namun saya berhasil untuk membenarkan link yang bermasalah tersebut karna ternyata link tersebut bermasalah di file excel tersebut dan saya juga cukup mendapat kemudahan dikarenakan platform saya adalah X atau Twitter. Saya merasa sangat terbantu dengan banyaknya fitur yang dapat mempermudah tugas saya seperti adanya fitur advanced yang memungkinkan kita mengatur waktu tanggal post dan keyword pada post. Selain itu, platform X sendiri memiliki banyak akun yang dikhususkan untuk loker IT.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440002', 'score' => 5, 'answer' => 'Cara saya menganalisis iklan pada lowongan adalah membaca seluruh isi pada lowongan tersebut dan mengecek apakah lowongan ini memiliki data yang sesuai untuk keperluan proyek. Saya juga menyadari ada beberapa lowongan yang menurut saya tidak memberikan info cukup baik karena informasi yang benar-benar tidak lengkap, sehingga saya tidak memasukkan lowongan tersebut.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440003', 'score' => 4, 'answer' => 'Saya cukup puas dengan struktur data yang dibangun oleh kelompok saya, namun menurut saya mungkin perlu ditingkatkan lagi dengan menambahkan contact person. Untuk seluruh data yang lain, menurut saya sudah baik dan cukup lengkap.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440004', 'score' => 4, 'answer' => 'Saya telah menginputkan data cukup lengkap namun saya menyadari kesalahan saya bahwa saya cukup tidak teliti dalam memasukkan link tautan sumber. Seperti yang saya ceritakan sebelumnya, saya mengalami kendala dimana beberapa tautan tidak dapat dibuka karena saat penginputan link ke dalam spreadsheet terjadi perubahan pada link tersebut.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440005', 'score' => 5, 'answer' => 'Dalam penggabungan peran kelompok, saya merasa aktif karena saya membantu mengusulkan beberapa unsur data yang akan dimasukkan. Selain itu, saya membantu mencatat beberapa poin yang akan dimasukkan serta memberikan banyak pendapat saya mengenai saran untuk struktur data.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440006', 'score' => 4, 'answer' => 'Dalam komunikasi, saya merasa cukup baik karena saya selalu berusaha untuk menginformasikan temuan saya dan hasil kerja saya serta menanyakan perihal anggota yang lain. Namun, terkadang masih sering terjadi miss komunikasi.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440007', 'score' => 5, 'answer' => 'Situasi yang terjadi adalah saat adanya miss komunikasi yang cukup fatal jika tidak segera dibicarakan. Selain itu, juga ada situasi saat menentukan keputusan struktur data.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440008', 'score' => 5, 'answer' => 'Untuk tantangan dalam kelompok, saya menyadari ada beberapa platform yang ternyata cukup sulit mencari lowongan tersebut, maka saya berinisiatif membantu agar mempercepat proses pengerjaan.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440009', 'score' => 3, 'answer' => 'Saat mendapatkan tugas, lebih baik dikerjakan pada saat jam matkul tersebut. Namun, saya sadar bahwa tugas pada jurusan ini cukup banyak dan tidak mungkin memberi prioritas penuh pada satu matkul saja. Maka dari itu, mencicil tugas pada waktu tertentu dapat membantu menghindari keterlambatan.'],
                ],
            ],
            [
                'mahasiswa_id' => 'aa483968-7f8c-8c05-af8e-936823bf4bdm', // HILMI
                'dosen_id' => 'ra483968-7f8c-4c03-af8c-936823bf6bdu',
                'status' => 'submitted',
                'answers' => [
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440001', 'score' => 4, 'answer' => 'Awalnya saya agak kesulitan mencari informasi tentang lowongan kerja di Instagram, sudah memakai hashtag juga hanya ada sedikit, namun ketika diberi oleh ketua, saya diberitahu untuk mencari terlebih dahulu di Google dengan keyword "akun Instagram info lowongan kerja IT". Lalu muncul akun Instagram yang berkaitan dengan lowongan kerja IT. Lalu ada beberapa postingan Instagram yang data informasinya sama, dan saya memilih satu data postingan saja untuk dikumpulkan datanya.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440002', 'score' => 4, 'answer' => 'Pertama saya menganalisis apa nama perusahaan, lalu posisi pekerjaannya, pendidikan minimalnya apa, batasan umurnya berapa tahun, lalu hardskill yang harus dimilikinya apa, minimal berapa lama pengalaman kerja, jenis kelaminnya apa (jika tidak mencantumkan, berarti Laki-laki/Perempuan), lalu domisili perusahaannya berada di mana, sistem kerjanya apa.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440003', 'score' => 4, 'answer' => 'Keputusan yang diambil oleh kelompok kami dalam menentukan struktur data yaitu awalnya kami berdiskusi terlebih dahulu untuk menentukan struktur datanya yang menurut kami penting, lalu kelompok kami memutuskan struktur datanya sebagai berikut: 1. Nama perusahaan 2. Posisi Pekerjaan 3. Jenjang minimal 4. Jurusan 5. Batasan umur 6. Hardskill 7. Pengalaman kerja 8. Jenis kelamin 9. Domisili 10. Sistem kerja 11. Platform 12. Link sumber. Menurut saya, struktur data sudah sesuai dengan kebutuhan kelompok.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440004', 'score' => 4, 'answer' => 'Setelah menginput data yang dimasukkan ke data Excel, saya mengecek kembali data yang dimasukkan untuk meminimalisir kesalahan dalam penulisan kata.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440005', 'score' => 4, 'answer' => 'Kelompok kami melakukan proses penggabungan data dengan membagi menjadi per sheet, jadi tiap kelompok menempati sheet yang berbeda-beda agar lebih mudah dalam menganalisis struktur dan isian data, tiap anggota dibagi tugas untuk menganalisis struktur data dan isian data tiap data kelompok lain. Saya menganalisis struktur data dan isian data apa saja yang ada di kelompok 5, ada terdapat perbedaan dalam penamaan struktur data, ada beberapa struktur data yang tidak ada di kelompok kami, pada isian data saya menemukan isi data yang kurang efektif dan kurang lengkap.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440006', 'score' => 4, 'answer' => 'Saya berkomunikasi dan berkolaborasi dengan anggota kelompok diawali dengan diskusi terlebih dahulu sebelum pengerjaan proyek dimulai untuk mengetahui apa saja yang harus dilakukan dan target apa yang harus dicapai dalam pengerjaan proyek. Jika ada struktur data yang masih bingung, saya menanyakan maksudnya apa dan bagaimana. Kami terus berkomunikasi tiap pengerjaan proyek berlangsung, baik secara diskusi langsung maupun tidak langsung (melewati sosial media WhatsApp).'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440007', 'score' => 4, 'answer' => 'Ketika saya ada kesulitan dalam pengumpulan data, saya menanyakannya kepada anggota lain untuk bagaimana cara pengisian datanya.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440008', 'score' => 4, 'answer' => 'Kami berdiskusi secara intens ketika pengerjaan proyek berlangsung. Saya menangani tantangan dalam mencari info lowongan pekerjaan yaitu ada beberapa postingan yang sama, jadi saya hanya mengambil satu postingan dari beberapa postingan yang sama agar tidak ada data yang double.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440009', 'score' => 4, 'answer' => 'Menyicilnya dari awal tugas diberikan dan berkolaborasi dengan anggota lain jika butuh tenaga lebih untuk mengumpulkan tugas proyek.'],
                ],
            ],
            [
                'mahasiswa_id' => 'aa483968-7f8c-3c05-af8e-936823bf9bdr', // HANIF
                'dosen_id' => 'ra483968-7f8c-4c03-af8c-936823bf6bdu',
                'status' => 'submitted',
                'answers' => [
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440001', 'score' => 4, 'answer' => 'Kesulitan dalam media sosial TikTok ketika saya membantu Irvan dalam mencari lowongan kerja karena banyak yang berupa video, bukan gambar.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440002', 'score' => 4, 'answer' => 'Saya menganalisis lowongan kerja dengan melihat struktur data yang telah kami buat, kemudian saya melihat satu per satu komponen yang ada pada iklan tersebut dan mengetikkannya pada kolom-kolom struktur data tersebut.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440003', 'score' => 5, 'answer' => 'Saya mengambil keputusan untuk membuat struktur data yang berisi nama perusahaan, domisili perusahaan, posisi yang diminta, batas umur, gender, minimal pendidikan, hardskill, pengalaman kerja, sistem kerja, nama platform tempat kami mengumpulkan iklan, serta link dari iklan tersebut.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440004', 'score' => 4, 'answer' => 'Saya melakukan input data pada baris yang saya klik link URL-nya sehingga saya merasa data yang saya input sudah akurat. Jika saya merasa ragu, saya mengecek kembali link yang ada pada baris yang saya isi apakah benar atau tidak.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440005', 'score' => 5, 'answer' => 'Saya mengusulkan untuk melakukan diskusi antar ketua kelompok saja karena jika satu kelas ikut rapat, itu kurang efektif.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440006', 'score' => 5, 'answer' => 'Saya selalu berkomunikasi dengan baik terhadap anggota kelompok saya. Saya sering meminta pendapat mereka, menentukan hal yang harus dilakukan, serta membangun komunikasi dalam kelompok, baik untuk tugas maupun hubungan sosial.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440007', 'score' => 5, 'answer' => 'Saya memimpin diskusi dalam menentukan struktur data kelas, mendengarkan pendapat, serta melakukan voting jika terdapat perbedaan pendapat yang signifikan.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440008', 'score' => 4, 'answer' => 'Ketika menemukan masalah, saya mencari solusinya sendiri terlebih dahulu. Jika tidak menemukan solusi, saya membuka ruang diskusi dengan anggota kelompok lain.'],
                    ['question_id' => '550e8400-e29b-41d4-a716-446655440009', 'score' => 4, 'answer' => 'Saya dan kelompok saya baik dalam mengatur waktu. Kami selalu memulai dengan berdoa, berdiskusi, dan menentukan target. Meskipun pada awalnya kami kesulitan, akhirnya kami dapat menyelesaikan tugas tepat waktu dan sering lebih cepat dari yang ditentukan.'],
                ],

            ],


        ];

        foreach ($answers as $data) {
            foreach ($data['answers'] as $answer) {
                DB::table('answers')->insert([
                    'id' => Str::uuid(),
                    'mahasiswa_id' => $data['mahasiswa_id'],
                    'dosen_id' => $data['dosen_id'],
                    'question_id' => $answer['question_id'],
                    'answer' => $answer['answer'],
                    'score' => $answer['score'],
                    'status' => $data['status'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }
}
