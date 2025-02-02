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
        Schema::create('assessment', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('tahun_ajaran');
            $table->string('nama_proyek');
            $table->char('type', 255);
            $table->string('pertanyaan', 255);
            $table->string('aspek', 255);
            $table->string('kriteria', 255);
            $table->timestamps();

            $table->foreign(['aspek', 'kriteria'])
                ->references(['aspek', 'kriteria'])
                ->on('type_criteria')
                ->onDelete('cascade'); 

            $table->foreign(['tahun_ajaran', 'nama_proyek'])
                ->references(['tahun_ajaran', 'nama_proyek'])
                ->on('project')
                ->onDelete('cascade'); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assessment');
    }
};
