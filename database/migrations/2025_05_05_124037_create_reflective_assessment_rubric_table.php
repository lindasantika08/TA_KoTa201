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
        Schema::create('reflective_assessment_rubric', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('criteria_reflective', 255);
            $table->string('bobot_1');
            $table->string('bobot_2');
            $table->string('bobot_3');
            $table->string('bobot_4');
            $table->string('bobot_5');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reflective_assessment_rubric');
    }
};
