<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Project;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $prodiId = '5a5e8c63-1234-4abc-89de-56789abcdef0'; // D3 Teknik Informatika (Bisa disesuaikan)
       
        $projects = [
            [
                'id' => 'pj483968-7f8c-1c05-af8e-936823bf1ppj',
                'semester' => 'Ganjil',
                'batch_year' => '2024/2025',
                'project_name' => 'Project 1 : Pemanfaatan Aplikasi Perkantoran',
                'prodi_id' => $prodiId,
                'start_date' => Carbon::parse('2025-0-01'),
                'end_date' => Carbon::parse('2025-12-31'),
                'status' => 'Active',
            ],
        ];

        DB::table('project')->insert($projects);
    }
}
