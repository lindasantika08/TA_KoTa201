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
        Schema::create('answersPeer', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('peer_id');
            $table->uuid('question_id');
            $table->text('answer');
            $table->integer('score');
            $table->string('status');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('peer_id')->references('id')->on('users');
            $table->foreign('question_id')->references('id')->on('assessment');
            $table->unique(['question_id', 'user_id', 'peer_id'], 'unique_user_question');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('answersPeer');
    }
};
