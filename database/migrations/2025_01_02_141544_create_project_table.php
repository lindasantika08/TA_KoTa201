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
        Schema::create('project', function (Blueprint $table) {
            $table->string('semester');
            $table->string('tahun_ajaran');
            $table->string('nama_proyek');
            $table->string('jurusan');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status');
            $table->timestamps();
            $table->softDeletes();

            $table->primary(['tahun_ajaran', 'nama_proyek']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project');
    }
};
