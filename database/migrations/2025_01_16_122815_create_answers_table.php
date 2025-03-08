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
        Schema::create('answers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('mahasiswa_id')->nullable()->constrained('mahasiswa');
            $table->foreignUuid('question_id')->constrained('assessment');
            $table->foreignUuid('dosen_id')->nullable()->constrained('dosen');
            $table->longText('answer');
            $table->integer('score');
            $table->string('status')->default('pending');
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['question_id', 'mahasiswa_id'], 'unique_user_question');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('answers');
    }
};
