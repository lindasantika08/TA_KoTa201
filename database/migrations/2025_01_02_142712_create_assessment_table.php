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
            $table->char('type', 255);
            $table->string('pertanyaan', 255);
            $table->string('aspek', 255);
            $table->string('kriteria', 255); // Pastikan panjangnya sama dengan `type_criteria`
            $table->timestamps();

            // Foreign key constraint
            $table->foreign(['aspek', 'kriteria'])
                ->references(['aspek', 'kriteria'])
                ->on('type_criteria')
                ->onDelete('cascade'); // Opsional
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
