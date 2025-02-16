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
                'D3 Teknik Sipil',
                'D4 Teknik Konstruksi Gedung',
                'D4 Teknik Perawatan dan Perbaikan Gedung'
            ],
            'Teknik Mesin' => [
                'D3 Teknik Mesin',
                'D4 Teknik Mesin Produksi dan Perawatan',
                'D4 Teknologi Rekayasa Manufaktur'
            ],
            'Teknik Elektro' => [
                'D3 Teknik Elektronika',
                'D3 Teknik Listrik',
                'D3 Teknik Telekomunikasi',
                'D4 Teknik Elektronika',
                'D4 Teknik Otomasi Industri'
            ],
            'Teknik Komputer dan Informatika' => [
                'D3 Teknik Informatika',
                'D4 Teknik Informatika'
            ],
            'Teknik Refrigerasi dan Tata Udara' => [
                'D3 Teknik Pendingin dan Tata Udara',
                'D4 Teknik Pendingin dan Tata Udara'
            ],
            'Teknik Konversi Energi' => [
                'D3 Teknik Konversi Energi',
                'D4 Teknologi Pembangkit Tenaga Listrik'
            ],
            'Teknik Kimia' => [
                'D3 Teknik Kimia',
                'D4 Teknik Kimia Produksi Bersih'
            ],
            'Akuntansi' => [
                'D3 Akuntansi',
                'D4 Akuntansi Manajemen Pemerintahan',
                'D4 Keuangan Syariah'
            ],
            'Administrasi Niaga' => [
                'D3 Administrasi Bisnis',
                'D4 Administrasi Bisnis',
                'D4 Manajemen Pemasaran'
            ],
            'Bahasa Inggris' => [
                'D3 Bahasa Inggris',
                'D4 Bahasa Inggris'
            ]
        ];
        // Create prodi records
        foreach ($majors as $major) {
            if (isset($prodiData[$major->major_name])) {
                foreach ($prodiData[$major->major_name] as $prodiName) {
                    Prodi::create([
                        'id' => Str::uuid(),
                        'major_id' => $major->id,
                        'prodi_name' => $prodiName
                    ]);
                }
            }
        }
    }
}