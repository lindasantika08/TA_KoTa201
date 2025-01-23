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
        Schema::table('answersPeer', function (Blueprint $table) {
            $table->uuid('peer_id')->nullable()->change(); // Mengubah peer_id menjadi nullable
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('answersPeer', function (Blueprint $table) {
            $table->uuid('peer_id')->nullable(false)->change(); // Mengembalikan peer_id menjadi non-nullable
        });
    }
};
