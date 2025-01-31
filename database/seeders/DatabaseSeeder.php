<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Database\Seeders\UserSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Memanggil UserSeeder
        $this->call(UserSeeder::class);
        $this->call(MajorSeeder::class);
        $this->call(ProdiSeeder::class);
        $this->call(ClassSeeder::class);
        $this->call(DosenSeeder::class);
        $this->call(MahasiswaSeeder::class);

    }
}
