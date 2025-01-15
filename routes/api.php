<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Dosen\AssessmentController;
use App\Http\Controllers\Dosen\ProjectController;
use App\Http\Controllers\Dosen\KelolaProyekController;

use App\Http\Controllers\Mahasiswa\AssessmentMahasiswa;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function () {
        return auth()->user();
    });
    Route::get('/export-self-assessment', [AssessmentController::class, 'exportExcel']);
    Route::put('/logout', [AuthController::class, 'logout']);
    Route::post('/project', [KelolaProyekController::class, 'AddProyek']);
    Route::get('/projects', [KelolaProyekController::class, 'getProjects']);
    Route::get('/project-dropdown', [ProjectController::class, 'index']);
    Route::get('/self-assessment', [AssessmentMahasiswa::class, 'getDataSelf']);
    Route::get('/assessment/projects', [ProjectController::class, 'getProjectsWithAssessments']);
});
