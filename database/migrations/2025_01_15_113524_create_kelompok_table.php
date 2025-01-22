<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('kelompok', function (Blueprint $table) {
            $table->uuid('id')->primary(); // UUID sebagai primary key
            $table->string('tahun_ajaran'); // Tahun ajaran
            $table->string('nama_proyek'); // Nama proyek
            $table->uuid('user_id'); // User ID sebagai foreign key mengarah ke tabel users
            $table->string('kelompok'); // Kolom kelompok
            $table->uuid('dosen_id');
            $table->timestamps();

            // Foreign key constraint untuk 'tahun_ajaran' dan 'nama_proyek' ke tabel 'project'
            $table->foreign(['tahun_ajaran', 'nama_proyek'])
                ->references(['tahun_ajaran', 'nama_proyek'])
                ->on('project')
                ->onDelete('cascade');

            // Foreign key constraint untuk 'user_id' yang mengarah ke tabel 'users'
            $table->foreign('user_id')
                ->references('id') // Mengacu pada kolom 'id' di tabel 'users'
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('dosen_id')
                ->references('id') // Mengacu pada kolom 'id' di tabel 'users'
                ->on('users')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kelompok');
    }
};
