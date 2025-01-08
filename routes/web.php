<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

// Route untuk halaman login
Route::get('/login', [AuthController::class, 'index'])->name('login');

// Route untuk proses login
Route::post('/login', [AuthController::class, 'login']);

// Route untuk dashboard (hanya bisa diakses jika terautentikasi)
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard'); // Merender halaman dashboard
    })->name('dashboard');
});
