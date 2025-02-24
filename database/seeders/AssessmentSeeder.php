<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AssessmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('assessment')->insert([
            // Self Assessment
            [
                'id' => '550e8400-e29b-41d4-a716-446655440001',
                'batch_year' => '2024/2025',
                'project_id' => 'pj483968-7f8c-1c05-af8e-936823bf1ppj',
                'type' => 'selfAssessment',
                'question' => 'Seberapa banyak iklan lowongan kerja yang berhasil Anda kumpulkan dari platform yang ditugaskan?',
                'criteria_id' => 'tc483968-7f8c-4c01-af8a-936823bf6btc',
                'is_published' => 1,
                'end_date' => now()->addDays(30),
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => '550e8400-e29b-41d4-a716-446655440002',
                'batch_year' => '2024/2025',
                'project_id' => 'pj483968-7f8c-1c05-af8e-936823bf1ppj',
                'type' => 'selfAssessment',
                'question' => 'Seberapa baik Anda memahami informasi yang terdapat dalam iklan lowongan kerja yang Anda kumpulkan?',
                'criteria_id' => 'tc483968-7f8c-4c02-af8a-936823bf6btc',
                'is_published' => 1,
                'end_date' => now()->addDays(30),
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => '550e8400-e29b-41d4-a716-446655440003',
                'batch_year' => '2024/2025',
                'project_id' => 'pj483968-7f8c-1c05-af8e-936823bf1ppj',
                'type' => 'selfAssessment',
                'question' => 'Seberapa puas Anda dengan struktur data yang Anda usulkan?',
                'criteria_id' => 'tc483968-7f8c-4c03-af8a-936823bf6btc',
                'is_published' => 1,
                'end_date' => now()->addDays(30),
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => '550e8400-e29b-41d4-a716-446655440004',
                'batch_year' => '2024/2025',
                'project_id' => 'pj483968-7f8c-1c05-af8e-936823bf1ppj',
                'type' => 'selfAssessment',
                'question' => 'Seberapa teliti dan lengkap Anda dalam menginputkan data ke Excel?',
                'criteria_id' => 'tc483968-7f8c-4c04-af8a-936823bf6btc',
                'is_published' => 1,
                'end_date' => now()->addDays(30),
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => '550e8400-e29b-41d4-a716-446655440005',
                'batch_year' => '2024/2025',
                'project_id' => 'pj483968-7f8c-1c05-af8e-936823bf1ppj',
                'type' => 'selfAssessment',
                'question' => 'Seberapa efektif Anda berkontribusi dalam menggabungkan data dan struktur data dari kelompok lain?',
                'criteria_id' => 'tc483968-7f8c-4c05-af8a-936823bf6btc',
                'is_published' => 1,
                'end_date' => now()->addDays(30),
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => '550e8400-e29b-41d4-a716-446655440006',
                'batch_year' => '2024/2025',
                'project_id' => 'pj483968-7f8c-1c05-af8e-936823bf1ppj',
                'type' => 'selfAssessment',
                'question' => 'Seberapa baik Anda bekerja sama dengan anggota kelompok Anda?',
                'criteria_id' => 'tc483968-7f8c-4c06-af8a-936823bf6btc',
                'is_published' => 1,
                'end_date' => now()->addDays(30),
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => '550e8400-e29b-41d4-a716-446655440007',
                'batch_year' => '2024/2025',
                'project_id' => 'pj483968-7f8c-1c05-af8e-936823bf1ppj',
                'type' => 'selfAssessment',
                'question' => 'Seberapa baik kemampuan Anda dalam menyampaikan ide dan solusi kepada anggota kelompok lain?',
                'criteria_id' => 'tc483968-7f8c-4c07-af8a-936823bf6btc',
                'is_published' => 1,
                'end_date' => now()->addDays(30),
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => '550e8400-e29b-41d4-a716-446655440008',
                'batch_year' => '2024/2025',
                'project_id' => 'pj483968-7f8c-1c05-af8e-936823bf1ppj',
                'type' => 'selfAssessment',
                'question' => 'Seberapa baik Anda dalam mengidentifikasi dan menyelesaikan masalah yang muncul selama proyek?',
                'criteria_id' => 'tc483968-7f8c-4c08-af8a-936823bf6btc',
                'is_published' => 1,
                'end_date' => now()->addDays(30),
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => '550e8400-e29b-41d4-a716-446655440009',
                'batch_year' => '2024/2025',
                'project_id' => 'pj483968-7f8c-1c05-af8e-936823bf1ppj',
                'type' => 'selfAssessment',
                'question' => 'Seberapa baik Anda dalam mengelola waktu dan menyelesaikan tugas sesuai tenggat waktu?',
                'criteria_id' => 'tc483968-7f8c-4c09-af8a-936823bf6btc',
                'is_published' => 1,
                'end_date' => now()->addDays(30),
                'created_at' => now(),
                'updated_at' => now()
            ],

            // Peer Assessment
            [
                'id' => '550e8400-e29b-41d4-a716-446655440010',
                'batch_year' => '2024/2025',
                'project_id' => 'pj483968-7f8c-1c05-af8e-936823bf1ppj',
                'type' => 'peerAssessment',
                'question' => 'Seberapa baik [Nama] dalam mengumpulkan iklan lowongan kerja dari platform yang ditugaskan?',
                'criteria_id' => 'tc483968-7f8c-4c01-af8a-936823bf6btc',
                'is_published' => 1,
                'end_date' => now()->addDays(30),
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => '550e8400-e29b-41d4-a716-446655440011',
                'batch_year' => '2024/2025',
                'project_id' => 'pj483968-7f8c-1c05-af8e-936823bf1ppj',
                'type' => 'peerAssessment',
                'question' => 'Bagaimana pemahaman [Nama] terhadap isi iklan yang dikumpulkan?',
                'criteria_id' => 'tc483968-7f8c-4c02-af8a-936823bf6btc',
                'is_published' => 1,
                'end_date' => now()->addDays(30),
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => '550e8400-e29b-41d4-a716-446655440012',
                'batch_year' => '2024/2025',
                'project_id' => 'pj483968-7f8c-1c05-af8e-936823bf1ppj',
                'type' => 'peerAssessment',
                'question' => 'Seberapa baik struktur data yang diusulkan oleh [Nama]?',
                'criteria_id' => 'tc483968-7f8c-4c03-af8a-936823bf6btc',
                'is_published' => 1,
                'end_date' => now()->addDays(30),
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => '550e8400-e29b-41d4-a716-446655440013',
                'batch_year' => '2024/2025',
                'project_id' => 'pj483968-7f8c-1c05-af8e-936823bf1ppj',
                'type' => 'peerAssessment',
                'question' => 'Seberapa teliti [Nama] dalam menginputkan data ke Excel?',
                'criteria_id' => 'tc483968-7f8c-4c04-af8a-936823bf6btc',
                'is_published' => 1,
                'end_date' => now()->addDays(30),
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => '550e8400-e29b-41d4-a716-446655440014',
                'batch_year' => '2024/2025',
                'project_id' => 'pj483968-7f8c-1c05-af8e-936823bf1ppj',
                'type' => 'peerAssessment',
                'question' => 'Seberapa efektif Anda berkontribusi dalam menggabungkan data dan struktur data dari kelompok lain?',
                'criteria_id' => 'tc483968-7f8c-4c05-af8a-936823bf6btc',
                'is_published' => 1,
                'end_date' => now()->addDays(30),
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => '550e8400-e29b-41d4-a716-446655440015',
                'batch_year' => '2024/2025',
                'project_id' => 'pj483968-7f8c-1c05-af8e-936823bf1ppj',
                'type' => 'peerAssessment',
                'question' => 'Seberapa aktif [Nama] terlibat dalam penggabungan data dengan kelompok lain?',
                'criteria_id' => 'tc483968-7f8c-4c06-af8a-936823bf6btc',
                'is_published' => 1,
                'end_date' => now()->addDays(30),
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => '550e8400-e29b-41d4-a716-446655440016',
                'batch_year' => '2024/2025',
                'project_id' => 'pj483968-7f8c-1c05-af8e-936823bf1ppj',
                'type' => 'peerAssessment',
                'question' => 'Seberapa baik [Nama] bekerja sama dengan anggota kelompok?',
                'criteria_id' => 'tc483968-7f8c-4c07-af8a-936823bf6btc',
                'is_published' => 1,
                'end_date' => now()->addDays(30),
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => '550e8400-e29b-41d4-a716-446655440017',
                'batch_year' => '2024/2025',
                'project_id' => 'pj483968-7f8c-1c05-af8e-936823bf1ppj',
                'type' => 'peerAssessment',
                'question' => 'Seberapa efektif [Nama] dalam berkomunikasi dengan anggota kelompok?',
                'criteria_id' => 'tc483968-7f8c-4c08-af8a-936823bf6btc',
                'is_published' => 1,
                'end_date' => now()->addDays(30),
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => '550e8400-e29b-41d4-a716-446655440018',
                'batch_year' => '2024/2025',
                'project_id' => 'pj483968-7f8c-1c05-af8e-936823bf1ppj',
                'type' => 'peerAssessment',
                'question' => 'Seberapa baik [Nama] dalam menyelesaikan tugas tepat waktu?',
                'criteria_id' => 'tc483968-7f8c-4c09-af8a-936823bf6btc',
                'is_published' => 1,
                'end_date' => now()->addDays(30),
                'created_at' => now(),
                'updated_at' => now()
            ],

        ]);
    }
}
