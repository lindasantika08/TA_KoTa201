<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Dosen\AssessmentController;
use App\Http\Controllers\Dosen\AnswerController;
use App\Http\Controllers\Dosen\DashboardDosen;
use App\Http\Controllers\Dosen\ProjectController;
use App\Http\Controllers\Dosen\KelolaProyekController;
use App\Http\Controllers\Dosen\KelolaKelompokController;
use App\Http\Controllers\Dosen\UsersController;

use App\Http\Controllers\Mahasiswa\AssessmentMahasiswa;
use App\Http\Controllers\Mahasiswa\SelfAssessment;
use App\Http\Controllers\Mahasiswa\PeerAssessment;
use App\Models\AnswersPeer;
use Illuminate\Support\Facades\Auth;
use SebastianBergmann\CodeCoverage\Report\Html\Dashboard;

Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function () {
        return auth()->user();
    });

    Route::get('/user-role', function () {
        $user = Auth::user();
        return response()->json(['role' => $user->role]);
    })->middleware('auth:sanctum');
    //dosen
    Route::get('/export-self-assessment', [AssessmentController::class, 'exportExcel']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/project', [KelolaProyekController::class, 'AddProyek']);
    Route::get('/projects', [KelolaProyekController::class, 'getProjects']);
    Route::get('/project-dropdown', [ProjectController::class, 'index']);
    Route::get('/get-question-id', [AnswerController::class, 'getQuestionId']);
    Route::get('/user-info-dosen', [AnswerController::class, 'getUserInfoDosen']);
    Route::get('/questions-dosen', [SelfAssessment::class, 'getQuestionsByProject']);
    Route::get('/answers/list', [AnswerController::class, 'getListAnswers']);
    Route::get('/answersPeer/list', [AnswerController::class, 'getListAnswersPeer']);
    Route::get('/answersKelompokPeer/list', [AnswerController::class, 'getListAnswersKelompokPeer']);
    Route::get('/assessment/projects', [ProjectController::class, 'getProjectsWithAssessments']);
    Route::get('/proyek-self-assessment', [ProjectController::class, 'getDataSelf']);
    Route::get('/proyek-Peer-assessment', [ProjectController::class, 'getDataPeer']);
    Route::get('/kelola-kelompok/export', [KelolaKelompokController::class, 'exportTemplate']);
    Route::post('/kelola-kelompok/import', [KelolaKelompokController::class, 'importData']);
    Route::post('/save-answer-peerDosen', [AnswerController::class, 'AnswersPeerDosen']);
    Route::get('/users/search', [AnswerController::class, 'searchByNip']);
    Route::get('/questions-peerDosen', [AnswerController::class, 'getQuestionsByProjectPeer']);
    Route::get('/kelompok-dosen', [AnswerController::class, 'getKelompokDosen']);
    Route::get('/answered-peers-dosen', [AnswerController::class, 'answeredPeersDosen']);
    Route::get('/get-answer-peerDosen/{questionId}', [AnswerController::class, 'getAnswerPeerDosen']);
    Route::post('/save-all-answers-peerDosen', [AnswerController::class, 'saveAllAnswersPeerDosen']);
    Route::get('/answers/statistics', [AnswerController::class, 'getStatistics']);
    Route::get('projects/active', [DashboardDosen::class, 'getActiveProjects']);
    Route::post('/changeStatus', [ProjectController::class, 'changeStatus']);
    Route::get('/answers/get-details', [AnswerController::class, 'getDetailsAnswer']);


    //mahasiswa
    Route::get('/bobot', [SelfAssessment::class, 'getFilteredBobot']);
    Route::get('/self-assessment', [AssessmentMahasiswa::class, 'getDataSelf']);
    Route::get('/peer-assessment', [AssessmentMahasiswa::class, 'getDataPeer']);
    Route::get('/questions', [SelfAssessment::class, 'getQuestionsByProject']);
    Route::get('/questions-peer', [PeerAssessment::class, 'getQuestionsByProject']);
    Route::get('/type-kriteria', [SelfAssessment::class, '']);
    Route::post('/save-answer', [SelfAssessment::class, 'saveAnswer']);
    Route::get('/proyek-self-assessment', [ProjectController::class, 'getDataSelf']);
    Route::get('/proyek-Peer-assessment', [ProjectController::class, 'getDataPeer']);
    Route::get('/kelola-kelompok/export', [KelolaKelompokController::class, 'exportTemplate']);
    Route::post('/kelola-kelompok/import', [KelolaKelompokController::class, 'importData']);

    Route::post('/save-answer-peer', [PeerAssessment::class, 'AnswersPeer']);
    Route::get('/kelompok', [AssessmentMahasiswa::class, 'getKelompokByUser']);
    Route::get('/users/search', [AssessmentMahasiswa::class, 'searchByNim']);
    Route::get('/user-info', [SelfAssessment::class, 'getUserInfo']);
    Route::get('/assessment/projects', [ProjectController::class, 'getProjectsWithAssessments']);
    Route::get('/proyek-self-assessment', [ProjectController::class, 'getDataSelf']);
    Route::get('/proyek-Peer-assessment', [ProjectController::class, 'getDataPeer']);
    Route::get('/get-answer/{questionId}', [SelfAssessment::class, 'getAnswer']);
    Route::post('/save-all-answers', [SelfAssessment::class, 'saveAllAnswers']);
    Route::get('/kelola-kelompok/export', [KelolaKelompokController::class, 'exportTemplate']);
    Route::post('/kelola-kelompok/import', [KelolaKelompokController::class, 'importData']);
    Route::post('/save-all-answers-peer', [PeerAssessment::class, 'saveAllAnswersPeer']);
    Route::get('/get-answer-peer/{questionId}', [PeerAssessment::class, 'getAnswerPeer']);
    Route::get('/answered-peers', [PeerAssessment::class, 'answeredPeers']);

    Route::get('/api/saved-answer-peer', [PeerAssessment::class, 'getSavedAnswer']);
});
