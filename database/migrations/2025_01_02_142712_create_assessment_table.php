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
            $table->string('batch_year');
            $table->foreignUuid('project_id')->constrained('project');
            $table->char('type', 255);
            $table->string('question', 255);
            $table->foreignUuid('criteria_id')->constrained('type_criteria');
            $table->boolean('is_published')->default(0);
            $table->date('end_date')->nullable();
            $table->timestamps();
            $table->softDeletes();

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
