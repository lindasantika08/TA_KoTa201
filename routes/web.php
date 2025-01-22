<?php

use App\Http\Controllers\Dosen\DashboardDosen;
use App\Http\Controllers\Dosen\AssessmentController;
use App\Http\Controllers\Dosen\ProjectController;
use App\Http\Controllers\Dosen\KelolaProyekController;
use App\Http\Controllers\Dosen\KelolaKelompokController;
use App\Http\Controllers\Dosen\FeedbackController;
use App\Http\Controllers\Dosen\ReportController;
use App\Http\Controllers\Dosen\AnswerController;
use App\Http\Controllers\Dosen\UserManagementController;

use App\Http\Controllers\Mahasiswa\AssessmentMahasiswa;
use App\Http\Controllers\Mahasiswa\DashboardMahasiswa;
use App\Http\Controllers\Mahasiswa\SelfAssessment;
use App\Http\Controllers\Mahasiswa\PeerAssessment;
use App\Http\Controllers\Mahasiswa\FeedbackMahasiswa;
use App\Http\Controllers\Mahasiswa\ReportMahasiswa;
use App\Http\Controllers\Mahasiswa\DetailSelfMahasiswa;
use App\Http\Controllers\Mahasiswa\DetailPeerMahasiswa;


use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', 'login');
Route::get('/login', [AuthController::class, 'index'])->name('login');
Route::middleware('auth')->group(function () {

    // Route untuk dosen
    Route::middleware(['role:dosen'])->prefix('dosen')->group(function () {
        Route::get('/dashboard', [DashboardDosen::class, 'dashboard'])->name('dashboard');
        Route::get('/dashboard/self', [DashboardDosen::class, 'dashboardself'])->name('dashboardself');
        Route::get('/dashboard/peer', [DashboardDosen::class, 'dashboardpeer'])->name('dashboardpeer');
        Route::get('/notifications', [DashboardDosen::class, 'notifications'])->name('notifications');
        Route::get('/profile', [DashboardDosen::class, 'profile'])->name('profile');
        Route::get('/self', [AssessmentController::class, 'self'])->name('dosen.self-assessment');
        Route::get('/peer', [AssessmentController::class, 'peer'])->name('dosen.peer-assessment');
        Route::post('/assessment/import', [AssessmentController::class, 'import'])->name('assessment.import');
        Route::get('/assessment/data', [AssessmentController::class, 'getData'])->name('assessment.data');
        Route::get('/assessment/data-with-bobot-self', [AssessmentController::class, 'getAssessmentsWithBobotSelf'])->name('dosen.assessment.data-with-bobot-self');
        Route::get('/assessment/data-with-bobot-peer', [AssessmentController::class, 'getAssessmentsWithBobotpeer'])->name('dosen.assessment.data-with-bobot-peer');
        Route::get('/assessment/create', [AssessmentController::class, 'create'])->name('CreateAssessment');
        Route::get('/assessment/projectsSelf', [ProjectController::class, 'getProjectsWithAssessmentsSelf']);
        Route::get('/assessment/projectsPeer', [ProjectController::class, 'getProjectsWithAssessmentsPeer']);
        Route::get('/export-self-assessment', [AssessmentController::class, 'exportExcel'])->name('dosen.export-self');
        Route::get('/kelola-proyek', [KelolaProyekController::class, 'KelolaProyekView'])->name('kelola.proyek');
        Route::post('/tambah-proyek', [KelolaProyekController::class, 'AddProyek'])->name('kelola-proyek.store');

        Route::get('/kelola-kelompok', [KelolaKelompokController::class, 'KelolaKelompok'])->name('KelolaKelompok');
        Route::get('/kelola-kelompok/create', [KelolaKelompokController::class, 'CreateKelompok'])->name('CreateKelompok');
        Route::get('/kelola-kelompok/kelompok/{id}', [KelolaKelompokController::class, 'showDetail'])->name('DetailKelompok');

        Route::get('/kelola-kelompok/export', [KelolaKelompokController::class, 'exportTemplate'])->name('kelola-kelompok.export');
        Route::post('/kelola-kelompok/import', [KelolaKelompokController::class, 'importData'])->name('kelola-kelompok.import');

        Route::get('/report', [ReportController::class, 'report'])->name('report');
        Route::get('/feedback', [FeedbackController::class, 'feedback'])->name('feedback');
        Route::get('/AnswerSelf', [AnswerController::class, 'answerSelf'])->name('dosen.answerSelf');
        Route::get('/AnswerPeer', [AnswerController::class, 'answerPeer'])->name('dosen.answerPeer');
        Route::get('/answers-self-assessment', [AnswerController::class, 'getListAnswersView']);
        Route::get('/answers-peer-assessment', [AnswerController::class, 'getListAnswersPeerView']);

        Route::get('/kelola-mahasiswa', [UserManagementController::class, 'KelolaMahasiswa'])->name('KelolaMahasiswa');
        Route::get('/kelola-mahasiswa/input', [UserManagementController::class, 'InputMahasiswa'])->name('InputMahasiswa');
        Route::get('/kelola-mahasiswa/export', [UserManagementController::class, 'ExportMahasiswa'])->name('ExportMahasiswa');
        Route::post('/kelola-mahasiswa/import', [UserManagementController::class, 'ImportMahasiswa'])->name('ImportMahasiswa');

        Route::get('/kelola-dosen', [UserManagementController::class, 'KelolaDosen'])->name('KelolaDosen');
    });

    //Route untuk Mahasiswa
    Route::middleware('role:mahasiswa')->prefix('mahasiswa')->group(function () {
        Route::get('/dashboard', [DashboardMahasiswa::class, 'dashboard'])->name('mahasiswa.dashboard');
        Route::get('/assessment/self', [AssessmentMahasiswa::class, 'selfAssessment'])->name('mahasiswa.assessment');
        Route::get('/assessment/peer', [AssessmentMahasiswa::class, 'peerAssessment'])->name('mahasiswa.assessment');
        Route::get('/assessment/self-assessment', [SelfAssessment::class, 'assessment'])->name('mahasiswa.assessment');
        Route::get('/assessment/peer-assessment', [PeerAssessment::class, 'PeerAssessment'])->name('mahasiswa.assessment');
        Route::get('/feedback', [FeedbackMahasiswa::class, 'feedbackMahasiswa'])->name('mahasiswa.feedback');
        Route::get('/report', [ReportMahasiswa::class, 'reportMahasiswa'])->name('mahasiswa.report');
        Route::get('/user-info', [SelfAssessment::class, 'getUserInfo'])->name('mahasiswa.info-student');
        Route::post('/peer-assessment/answers', [AssessmentMahasiswa::class, 'AnswersPeer']);
        Route::get('/peer-assessment/answers/{userId}', [AssessmentMahasiswa::class, 'getUserPeerAnswers']);
        Route::get('/peer-assessment/peer-detail', [DetailPeerMahasiswa::class, 'showDetail']);
        Route::get('/peer-assessment/self-detail', [DetailSelfMahasiswa::class, 'showDetail']);
    });
});
