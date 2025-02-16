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
        Schema::table('assessment', function (Blueprint $table) {
            $table->date('end_date')->nullable()->after('is_published');
        });
    }

    public function down(): void
    {
        Schema::table('assessment', function (Blueprint $table) {
            $table->dropColumn('end_date');
        });
    }
};
