<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Major;

class MajorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $majors = [
            ['id' => '19b2c063-715a-421e-84f9-8b27257a101c', 'major_name' => 'Teknik Sipil'],
            ['id' => Str::uuid(), 'major_name' => 'Teknik Mesin'],
            ['id' => Str::uuid(), 'major_name' => 'Teknik Elektro'],
            ['id' => '73597463-6c5f-4357-8733-e5f3edb26b44', 'major_name' => 'Teknik Komputer dan Informatika'],
            ['id' => Str::uuid(), 'major_name' => 'Teknik Refrigerasi dan Tata Udara'],
            ['id' => Str::uuid(), 'major_name' => 'Teknik Konversi Energi'],
            ['id' => Str::uuid(), 'major_name' => 'Teknik Kimia'],
            ['id' => Str::uuid(), 'major_name' => 'Akuntansi'],
            ['id' => Str::uuid(), 'major_name' => 'Administrasi Niaga'],
            ['id' => Str::uuid(), 'major_name' => 'Bahasa Inggris'],
        ];

        foreach ($majors as $major) {
            Major::create($major);
        }
    }
}
