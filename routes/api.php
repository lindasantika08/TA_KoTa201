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
use App\Http\Controllers\Dosen\ReportController;
use App\Http\Controllers\Dosen\UserManagementController;

use App\Http\Controllers\Mahasiswa\AssessmentMahasiswa;
use App\Http\Controllers\Mahasiswa\DashboardMahasiswa;
use App\Http\Controllers\Mahasiswa\SelfAssessment;
use App\Http\Controllers\Mahasiswa\PeerAssessment;
use App\Http\Controllers\Mahasiswa\DetailSelfMahasiswa;
use App\Http\Controllers\Mahasiswa\ReportMahasiswa;
use App\Http\Controllers\Mahasiswa\ProfileMahasiswa;
use Illuminate\Support\Facades\Auth;
// use SebastianBergmann\CodeCoverage\Report\Html\Dashboard;

Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->get('/validate-token', [AuthController::class, 'validateToken']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function () {
        return auth()->user();
    });

    Route::get('/user-role', function () {
        $user = Auth::user();
        return response()->json(['role' => $user->role]);
    })->middleware('auth:sanctum');

    //---------------------------------------------dosen---------------------------------------------//
    Route::get('/export-self-assessment', [AssessmentController::class, 'exportExcel']);
    Route::put('/logout', [AuthController::class, 'logout']);
    Route::post('/project', [KelolaProyekController::class, 'AddProyek']);
    Route::get('/majors', [KelolaProyekController::class, 'getMajors']);
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
    Route::get('/kelola-kelompok/get-profile/{user_id}', [KelolaKelompokController::class, 'getProfile']);
    Route::get('/kelola-kelompok/get-profile-photo/{user_id}', [KelolaKelompokController::class, 'getProfilePhoto']);

    Route::post('/save-answer-peerDosen', [AnswerController::class, 'AnswersPeerDosen']);
    Route::get('/users/search', [AnswerController::class, 'searchByNip']);
    Route::get('/questions-peerDosen', [AnswerController::class, 'getQuestionsByProjectPeer']);
    Route::get('/kelompok-dosen', [AnswerController::class, 'getKelompokDosen']);
    Route::get('/answered-peers-dosen', [AnswerController::class, 'answeredPeersDosen']);
    Route::get('/get-answer-peerDosen/{questionId}', [AnswerController::class, 'getAnswerPeerDosen']);
    Route::post('/save-all-answers-peerDosen', [AnswerController::class, 'saveAllAnswersPeerDosen']);
    Route::get('/answers/statistics', [AnswerController::class, 'getStatistics']);
    Route::get('/answers/statistics-peer', [AnswerController::class, 'getStatisticsPeer']);
    Route::get('projects/active', [DashboardDosen::class, 'getActiveProjects']);
    Route::post('/changeStatus', [ProjectController::class, 'changeStatus']);
    Route::get('/answers/get-details', [AnswerController::class, 'getDetailsAnswer']);
    Route::get('/answers/{id}', [AnswerController::class, 'showdetail']);
    Route::get('/dropdown-options', [ReportController::class, 'getDropdownOptions']);
    Route::get('/kelompok/report', [ReportController::class, 'getKelompokReport']);
    Route::get('/kelompok/report-detail', [ReportController::class, 'getScoreKelompok']);
    Route::get('/report/kelompok/answers', [ReportController::class, 'getKelompokAnswers']);

    Route::get('/get-mahasiswa', [UserManagementController::class, 'getMahasiswa']);
    Route::get('/get-dosen', [UserManagementController::class, 'getDosen']);
    Route::get('/get-jurusan', [UserManagementController::class, 'getJurusanList']);
    Route::get('/get-prodi/{majorId}', [UserManagementController::class, 'getProdiByMajor']);
    Route::get('/get-angkatan', [UserManagementController::class, 'getAngkatan']);
    Route::get('/get-class', [UserManagementController::class, 'getClass']);

    Route::post('/save-answer', [AssessmentController::class, 'saveAnswer']);
    Route::post('/save-all-answers', [AssessmentController::class, 'saveAllAnswers']);
    Route::get('/assessment/projects', [ProjectController::class, 'getProjectsWithAssessments']);
    Route::get('/proyek-self-assessment', [ProjectController::class, 'getDataSelf']);
    Route::get('/proyek-self-assessment', [ProjectController::class, 'getDataSelf']);
    Route::get('/proyek-Peer-assessment', [ProjectController::class, 'getDataPeer']);


    //-------------------------------------mahasiswa------------------------------------------------//
    // dashboard mhs
    Route::get('/projects-user', [DashboardMahasiswa::class, 'getUserProject']);
    Route::get('/assessment-status', [DashboardMahasiswa::class, 'getSelfAssessmentStatus']);
    Route::get('/count-peer', [DashboardMahasiswa::class, 'getPeerAssessmentDetails']);

    // self assessment mhs
    Route::get('/self-assessment', [AssessmentMahasiswa::class, 'getDataSelf']);
    Route::get('/bobot', [SelfAssessment::class, 'getFilteredBobot']);
    Route::get('/questions', [SelfAssessment::class, 'getQuestionsByProject']);
    Route::get('/type-kriteria', [SelfAssessment::class, '']);
    Route::post('/save-answer-mhs', [SelfAssessment::class, 'saveAnswer']);
    Route::get('/user-info', [SelfAssessment::class, 'getUserInfo']);
    Route::get('/get-answer/{questionId}', [SelfAssessment::class, 'getAnswer']);
    Route::get('/check-peer-data', [AssessmentMahasiswa::class, 'checkData']);
    Route::post('/save-all-answers', [SelfAssessment::class, 'saveAllAnswers']);

    // peer assessment mhs
    Route::get('/peer-assessment', [AssessmentMahasiswa::class, 'getDataPeer']);
    Route::get('/questions-peer', [PeerAssessment::class, 'getQuestionsByProject']);
    Route::post('/save-answer-peer', [PeerAssessment::class, 'AnswersPeer']);
    Route::post('/save-all-answers-peer', [PeerAssessment::class, 'saveAllAnswersPeer']);
    Route::get('/existing-peer-answers', [PeerAssessment::class, 'getExistingPeerAnswers']);
    Route::get('/answered-peers', [PeerAssessment::class, 'answeredPeers']);
    Route::get('/get-answer-peer/{questionId}', [PeerAssessment::class, 'getAnswerPeer']);
    Route::get('/kelompok', [AssessmentMahasiswa::class, 'getKelompokByUser']);
    Route::get('/users/search', [AssessmentMahasiswa::class, 'searchByNim']);

    // detail assessment
    Route::get('/user-detail-answer', [DetailSelfMahasiswa::class, 'getUserInfo']);
    Route::get('/detail-answer-self', [DetailSelfMahasiswa::class, 'getAnswerSelf']);

    //mahasiswa Report
    Route::get('/mahasiswa/projects', [ReportMahasiswa::class, 'getProjects']);
    Route::get('/project-score-details', [ReportMahasiswa::class, 'getProjectScoreDetails']);

    //Mahasiswa Profile
    Route::get('/get-profile', [ProfileMahasiswa::class, 'getProfile']);
    Route::get('/mahasiswa/get-profile-photo', [ProfileMahasiswa::class, 'getProfilePhoto']);
    Route::post('/mahasiswa/upload-profile-photo', [ProfileMahasiswa::class, 'uploadProfilePhoto']);
    Route::delete('/mahasiswa/delete-profile-photo', [ProfileMahasiswa::class, 'deleteProfilePhoto']);

});
