<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('project_id')->constrained('project');
            $table->foreignUuid('group_id')->constrained('groups');
            $table->foreignUuid('mahasiswa_id')->constrained('mahasiswa');
            $table->foreignUuid('typeCriteria_id')->constrained('type_criteria');
            $table->float('final_score_self')->nullable();
            $table->float('final_score_peer')->nullable();
            $table->foreignUuid('question_id')->constrained('assessment');
            $table->foreignUuid('peer_id')->nullable()->constrained('mahasiswa');
            $table->string('assessment_type')->nullable();
            $table->decimal('skor_self', 5, 2);
            $table->decimal('skor_peer', 5, 2);
            $table->decimal('selisih', 5, 2);
            $table->decimal('nilai_total', 5, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('reports');
    }
}
