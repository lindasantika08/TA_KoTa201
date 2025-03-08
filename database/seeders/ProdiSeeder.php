<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Prodi;
use App\Models\Major;

class ProdiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all majors
        $majors = Major::all();

        // Define study programs for each major
        $prodiData = [
            'Teknik Sipil' => [
                ['id' => Str::uuid(), 'prodi_name' => 'D3 Teknik Sipil'],
                ['id' => Str::uuid(), 'prodi_name' => 'D4 Teknik Konstruksi Gedung'],
                ['id' => Str::uuid(), 'prodi_name' => 'D4 Teknik Perawatan dan Perbaikan Gedung'],
            ],
            'Teknik Mesin' => [
                ['id' => Str::uuid(), 'prodi_name' => 'D3 Teknik Mesin'],
                ['id' => Str::uuid(), 'prodi_name' => 'D4 Teknik Mesin Produksi dan Perawatan'],
                ['id' => Str::uuid(), 'prodi_name' => 'D4 Teknologi Rekayasa Manufaktur'],
            ],
            'Teknik Elektro' => [
                ['id' => Str::uuid(), 'prodi_name' => 'D3 Teknik Elektronika'],
                ['id' => Str::uuid(), 'prodi_name' => 'D3 Teknik Listrik'],
                ['id' => Str::uuid(), 'prodi_name' => 'D3 Teknik Telekomunikasi'],
                ['id' => Str::uuid(), 'prodi_name' => 'D4 Teknik Elektronika'],
                ['id' => Str::uuid(), 'prodi_name' => 'D4 Teknik Otomasi Industri'],
            ],
            'Teknik Komputer dan Informatika' => [
                ['id' => '5a5e8c63-1234-4abc-89de-56789abcdef0', 'prodi_name' => 'D3 Teknik Informatika'], // ID ditentukan
                ['id' => Str::uuid(), 'prodi_name' => 'D4 Teknik Informatika'],
            ],
            'Teknik Refrigerasi dan Tata Udara' => [
                ['id' => Str::uuid(), 'prodi_name' => 'D3 Teknik Pendingin dan Tata Udara'],
                ['id' => Str::uuid(), 'prodi_name' => 'D4 Teknik Pendingin dan Tata Udara'],
            ],
            'Teknik Konversi Energi' => [
                ['id' => Str::uuid(), 'prodi_name' => 'D3 Teknik Konversi Energi'],
                ['id' => Str::uuid(), 'prodi_name' => 'D4 Teknologi Pembangkit Tenaga Listrik'],
            ],
            'Teknik Kimia' => [
                ['id' => Str::uuid(), 'prodi_name' => 'D3 Teknik Kimia'],
                ['id' => Str::uuid(), 'prodi_name' => 'D4 Teknik Kimia Produksi Bersih'],
            ],
            'Akuntansi' => [
                ['id' => Str::uuid(), 'prodi_name' => 'D3 Akuntansi'],
                ['id' => Str::uuid(), 'prodi_name' => 'D4 Akuntansi Manajemen Pemerintahan'],
                ['id' => Str::uuid(), 'prodi_name' => 'D4 Keuangan Syariah'],
            ],
            'Administrasi Niaga' => [
                ['id' => Str::uuid(), 'prodi_name' => 'D3 Administrasi Bisnis'],
                ['id' => Str::uuid(), 'prodi_name' => 'D4 Administrasi Bisnis'],
                ['id' => Str::uuid(), 'prodi_name' => 'D4 Manajemen Pemasaran'],
            ],
            'Bahasa Inggris' => [
                ['id' => Str::uuid(), 'prodi_name' => 'D3 Bahasa Inggris'],
                ['id' => Str::uuid(), 'prodi_name' => 'D4 Bahasa Inggris'],
            ]
        ];

        // Create prodi records
        foreach ($majors as $major) {
            if (isset($prodiData[$major->major_name])) {
                foreach ($prodiData[$major->major_name] as $prodi) {
                    Prodi::create([
                        'id' => $prodi['id'],
                        'major_id' => $major->id,
                        'prodi_name' => $prodi['prodi_name']
                    ]);
                }
            }
        }
    }
}
