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
        Schema::create('answers_peer', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('mahasiswa_id')->nullable()->constrained('mahasiswa');
            $table->foreignUuid('peer_id')->nullable()->constrained('mahasiswa');
            $table->foreignUuid('dosen_id')->nullable()->constrained('dosen');
            $table->uuid('question_id');
            $table->longText('answer');
            $table->integer('score');
            $table->string('status');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('question_id')->references('id')->on('assessment');
            $table->unique(['question_id', 'mahasiswa_id', 'peer_id'], 'unique_user_question');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('answers_peer');
    }
};
