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
        Schema::create('score', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('criteria_id');
            $table->foreign('criteria_id')->references('id')->on('criteria');
            $table->integer('total_score');
            $table->string('type_assessment');
            // $table->foreign('type_assessment')->references('type')->on('assessment');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('score');
    }
};
