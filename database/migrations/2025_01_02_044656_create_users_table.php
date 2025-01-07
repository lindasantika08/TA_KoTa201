<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('nip', 18)->unique()->nullable();
            $table->string('nim', 9)->unique()->nullable();
            $table->string('photo', 2048)->nullable();
            $table->enum('role', ['mahasiswa', 'dosen'])->default('mahasiswa');
            // $table->foreignUuid('created_by')->nullable();
            // $table->foreignUuid('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
