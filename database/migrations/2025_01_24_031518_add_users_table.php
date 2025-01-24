<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->year('angkatan')->nullable()->after('role'); // Tahun masuk
            $table->string('kelas', 10)->nullable()->after('angkatan'); // Kelas
            $table->string('prodi', 100)->nullable()->after('kelas'); // Program Studi
            $table->string('jurusan', 100)->nullable()->after('prodi'); // Jurusan
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['angkatan', 'kelas', 'prodi', 'jurusan']);
        });
    }
};
