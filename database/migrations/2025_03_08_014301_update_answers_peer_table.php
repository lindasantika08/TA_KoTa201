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
        Schema::table('answers_peer', function (Blueprint $table) {
            $table->integer('score_SLA')->nullable()->after('score');
            $table->float('similarity', 8, 4)->nullable()->after('score_SLA');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('answers_peer', function (Blueprint $table) {
            $table->dropColumn(['score_SLA', 'similarity']);
        });
    }
};
