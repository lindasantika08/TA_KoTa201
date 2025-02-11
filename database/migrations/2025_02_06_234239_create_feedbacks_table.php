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
        Schema::create('feedbacks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('mahasiswa_id')->nullable()->constrained('mahasiswa');
            $table->foreignUuid('dosen_id')->nullable()->constrained('dosen');
            $table->foreignUuid('peer_id')->constrained('mahasiswa');
            $table->foreignUuid('group_id')->constrained('groups');
            $table->string('feedback');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feedbacks');
    }
};
