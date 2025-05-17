<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::table('reports', function (Blueprint $table) {
        $table->float('final_score_self')->nullable();
        $table->float('final_score_peer')->nullable();
        $table->foreignUuid('question_id')->constrained('assessment');
        $table->foreignUuid('peer_id')->nullable()->constrained('mahasiswa');
        $table->string('assessment_type')->nullable();
    });
}

public function down()
{
    Schema::table('reports', function (Blueprint $table) {
        $table->dropColumn(['final_score_self', 'final_score_peer', 'question_id', 'peer_id', 'assessment_type']);
    });
}

};
