<?php

use App\Http\Controllers\Dosen\DashboardDosen;
use App\Http\Controllers\Dosen\AssessmentController;
use App\Http\Controllers\Dosen\KelolaProyekController;
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
        Route::get('/self', [AssessmentController::class, 'self'])->name('dosen.self-assessment');
        Route::get('/peer', [AssessmentController::class, 'peer'])->name('dosen.peer-assessment');
        Route::post('/assessment/import', [AssessmentController::class, 'import'])->name('assessment.import');
        Route::get('/assessment/data', [AssessmentController::class, 'getData'])->name('assessment.data');
        Route::get('/assessment/data-with-bobot', [AssessmentController::class, 'getAssessmentsWithBobot'])->name('assessment.data-with-bobot'); // Route baru untuk data dengan bobot
        Route::get('/export-self-assessment', [AssessmentController::class, 'exportExcel'])->name('dosen.export-self');
        Route::get('/kelola-proyek', [KelolaProyekController::class, 'KelolaProyekView'])->name('kelola.proyek');
        Route::post('/tambah-proyek', [KelolaProyekController::class, 'AddProyek'])->name('kelola-proyek.store');
    });

    //Route untuk Mahasiswa
    Route::middleware('role:mahasiswa')->prefix('mahasiswa')->group(function () {
        Route::get('/dashboard', [DashboardMahasiswa::class, 'dashboard'])->name('mahasiswa.dashboard');
    });
});
