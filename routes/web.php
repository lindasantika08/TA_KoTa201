<?php

use App\Http\Controllers\Dosen\DashboardDosen;
use App\Http\Controllers\Dosen\AssessmentController;
use App\Http\Controllers\Mahasiswa\DashboardMahasiswa;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', 'login');
Route::get('/login', [AuthController::class, 'index'])->name('login');
Route::middleware('auth')->group(function () {
    // Route untuk dosen
    Route::middleware(['role:dosen'])->prefix('dosen')->group(function () {
        Route::get('/dashboard', [DashboardDosen::class, 'dashboard'])->name('dashboard');
        Route::get('/notifications', [DashboardDosen::class, 'notifications'])->name('notifications');
        Route::get('/profile', [DashboardDosen::class, 'profile'])->name('profile');
        Route::get('/assessment/selfAssessment', [AssessmentController::class, 'selfAssessment'])->name('assessment.self');
        Route::get('/assessment/peer', [AssessmentController::class, 'peerAssessment'])->name('assessment.peer');
        Route::post('/assessment/import', [AssessmentController::class, 'import'])->name('assessment.import');
        Route::get('/assessment/data', [AssessmentController::class, 'getData'])->name('assessment.data');
        
    });

    //Route untuk Mahasiswa
    Route::middleware('role:mahasiswa')->prefix('mahasiswa')->group(function () {
        Route::get('/dashboard', [DashboardMahasiswa::class, 'dashboard'])->name('mahasiswa.dashboard');
    });
});
