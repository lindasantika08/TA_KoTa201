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
        Schema::create('reflective_writing', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('batch_year');
            $table->foreignUuid('project_id')->constrained('project');
            $table->integer('reflective_writing_order');
            $table->string('type', 30);
            $table->string('point_1', 255)->nullable();
            $table->string('point_2', 255)->nullable();
            $table->string('point_3', 255)->nullable();
            $table->string('point_4', 255)->nullable();
            $table->string('point_5', 255)->nullable();
            $table->boolean('is_published')->default(0);
            $table->date('end_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reflective_writing');
    }
};
