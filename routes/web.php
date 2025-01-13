<?php

use App\Http\Controllers\Dosen\DashboardDosen;
use App\Http\Controllers\Dosen\SelfAssessmentController;
use App\Http\Controllers\Dosen\PeerAssessmentController;

use App\Http\Controllers\Mahasiswa\DashboardMahasiswa;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', 'login');
Route::get('/login', [AuthController::class, 'index'])->name('login');
Route::middleware('auth')->group(function () {
    // Route untuk dosen
    Route::middleware(['role:dosen'])->prefix('dosen')->group(function (){
        Route::get('/dashboard', [DashboardDosen::class, 'dashboard'])->name('dashboard');
        Route::get('/export-self-assessment', [SelfAssessmentController::class, 'exportExcel'])->name('dosen.export-self');
        Route::get('/export-peer-assessment', [PeerAssessmentController::class, 'exportExcel'])->name('dosen.export-peer');
        Route::post('/import-self-assessment', [SelfAssessmentController::class, 'import'])->name('dosen.import-self');
        Route::get('/self', [SelfAssessmentController::class, 'index'])->name('dosen.self-assessment');
        Route::get('/peer', [PeerAssessmentController::class, 'index'])->name('dosen.peer-assessment');
    });

    //Route untuk Mahasiswa
    Route::middleware('role:mahasiswa')->prefix('mahasiswa')->group(function (){
        Route::get('/dashboard', [DashboardMahasiswa::class, 'dashboard'])->name('mahasiswa.dashboard');
    });
});
