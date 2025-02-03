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
        Schema::create('groups', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('batch_year');
            $table->string('angkatan')->nullable();
            $table->foreignUuid('project_id')->constrained('project');
            $table->foreignUuid('mahasiswa_id')->constrained('mahasiswa');
            $table->string('group'); 
            $table->foreignUuid('dosen_id')->constrained('dosen');
            $table->timestamps();
            $table->softDeletes();

            // $table->unique(['mahasiswa_id', 'project_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('groups');
    }
};
