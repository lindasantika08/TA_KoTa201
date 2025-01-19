<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Dosen\AssessmentController;
use App\Http\Controllers\Dosen\ProjectController;
use App\Http\Controllers\Dosen\KelolaProyekController;

use App\Http\Controllers\Mahasiswa\AssessmentMahasiswa;
use App\Http\Controllers\Mahasiswa\SelfAssessment;
use App\Http\Controllers\Mahasiswa\PeerAssessment;
use App\Models\AnswersPeer;

Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function () {
        return auth()->user();
    });
    //dosen
    Route::get('/export-self-assessment', [AssessmentController::class, 'exportExcel']);
    Route::put('/logout', [AuthController::class, 'logout']);
    Route::post('/project', [KelolaProyekController::class, 'AddProyek']);
    Route::get('/projects', [KelolaProyekController::class, 'getProjects']);
    Route::get('/project-dropdown', [ProjectController::class, 'index']);

    //mahasiswa
    Route::get('/bobot', [SelfAssessment::class, 'getFilteredBobot']);
    Route::get('/self-assessment', [AssessmentMahasiswa::class, 'getDataSelf']);
    Route::get('/peer-assessment', [AssessmentMahasiswa::class, 'getDataPeer']);
    Route::get('/questions', [SelfAssessment::class, 'getQuestionsByProject']);
    Route::get('/questions-peer', [PeerAssessment::class, 'getQuestionsByProject']);
    Route::get('/type-kriteria', [SelfAssessment::class, '']);
    Route::post('/save-answer', [SelfAssessment::class, 'saveAnswer']);
    Route::post('/save-answer-peer', [PeerAssessment::class, 'AnswersPeer']);
    Route::get('/kelompok', [AssessmentMahasiswa::class, 'getKelompokByUser']);
    Route::get('/users/search', [AssessmentMahasiswa::class, 'searchByNim']);
    Route::get('/user-info', [SelfAssessment::class, 'getUserInfo']);
    Route::get('/assessment/projects', [ProjectController::class, 'getProjectsWithAssessments']);
    Route::get('/proyek-self-assessment', [ProjectController::class, 'getDataSelf']);
    Route::get('/proyek-Peer-assessment', [ProjectController::class, 'getDataPeer']);
});
