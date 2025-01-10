<?php

use App\Http\Controllers\Dosen\DashboardDosen;
use App\Http\Controllers\Mahasiswa\DashboardMahasiswa;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', 'login');
Route::get('/login', [AuthController::class, 'index'])->name('login');
Route::middleware('auth')->group(function () {
    // Route untuk dosen
    Route::middleware(['role:dosen'])->prefix('dosen')->group(function (){
        Route::get('/dashboard', [DashboardDosen::class, 'dashboard'])->name('dashboard');
    });

    //Route untuk Mahasiswa
    Route::middleware('role:mahasiswa')->prefix('mahasiswa')->group(function (){
        Route::get('/dashboard', [DashboardMahasiswa::class, 'dashboard'])->name('mahasiswa.dashboard');
    });
});
