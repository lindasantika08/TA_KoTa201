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
            $table->uuid('user_id');
            $table->foreign('user_id')->references('id')->on('users');
            $table->uuid('question_id');
            $table->foreign('question_id')->references('id')->on('assessment');
            $table->string('answer');
            $table->integer('score');
            $table->string('status')->default('pending');
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['question_id', 'user_id'], 'unique_user_question');
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
