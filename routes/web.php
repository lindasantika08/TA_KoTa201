<?php

use App\Http\Controllers\Admin\DashboardAdminController;
use App\Http\Controllers\Admin\MajorAdminController;
use App\Http\Controllers\Admin\UserAdminController;

use App\Http\Controllers\Dosen\DashboardDosen;
use App\Http\Controllers\Dosen\AssessmentController;
use App\Http\Controllers\Dosen\ProjectController;
use App\Http\Controllers\Dosen\KelolaProyekController;
use App\Http\Controllers\Dosen\KelolaKelompokController;
use App\Http\Controllers\Dosen\FeedbackController;
use App\Http\Controllers\Dosen\ReportController;
use App\Http\Controllers\Dosen\AnswerController;
use App\Http\Controllers\Dosen\ProfileController;
use App\Http\Controllers\Dosen\UserManagementController;
use App\Http\Controllers\Dosen\PeerAssessmentDosen;

use App\Http\Controllers\Mahasiswa\AssessmentMahasiswa;
use App\Http\Controllers\Mahasiswa\DashboardMahasiswa;
use App\Http\Controllers\Mahasiswa\SelfAssessment;
use App\Http\Controllers\Mahasiswa\PeerAssessment;
use App\Http\Controllers\Mahasiswa\FeedbackMahasiswa;
use App\Http\Controllers\Mahasiswa\ReportMahasiswa;
use App\Http\Controllers\Mahasiswa\DetailSelfMahasiswa;
use App\Http\Controllers\Mahasiswa\DetailPeerMahasiswa;
use App\Http\Controllers\Mahasiswa\ProfileMahasiswa;
use App\Http\Controllers\Mahasiswa\NotificationMahasiswa;

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('sispa')->group(function () {
    Route::redirect('/', '/sispa/login');
    Route::get('/login', [AuthController::class, 'index'])->name('login');
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetForm'])->name('password.reset');

    Route::middleware('auth')->group(function () {

        // Route untuk dosen
        Route::middleware(['role:dosen'])->prefix('dosen')->group(function () {
            Route::get('/dashboard', [DashboardDosen::class, 'dashboard'])->name('dashboard');
            Route::get('/dashboard/self', [DashboardDosen::class, 'dashboardself'])->name('dashboardself');
            Route::get('/dashboard/peer', [DashboardDosen::class, 'dashboardpeer'])->name('dashboardpeer');
            Route::get('/notifications', [DashboardDosen::class, 'notifications'])->name('notifications');
    
            Route::get('/profile', [ProfileController::class, 'profile'])->name('profile.dosen');
    
            Route::get('/self', [AssessmentController::class, 'self'])->name('dosen.self-assessment');
            Route::get('/peer', [AssessmentController::class, 'peer'])->name('dosen.peer-assessment');
            Route::post('/assessment/import', [AssessmentController::class, 'import'])->name('assessment.import');
            Route::get('/assessment/data', [AssessmentController::class, 'getData'])->name('assessment.data');
            Route::get('/assessment/data-with-bobot-self', [AssessmentController::class, 'getAssessmentsWithBobotSelf'])->name('dosen.assessment.data-with-bobot-self');
            Route::get('/assessment/data-with-bobot-peer', [PeerAssessmentDosen::class, 'getAssessmentsWithBobotpeer'])->name('dosen.assessment.data-with-bobot-peer');
            Route::get('/assessment/create', [AssessmentController::class, 'create'])->name('CreateAssessment');
    
            Route::get('/export-self-assessment', [AssessmentController::class, 'exportExcel'])->name('dosen.export-self');
    
            Route::get('/assessment/projects-self', [ProjectController::class, 'getProjectsWithAssessmentsSelf']);
            Route::get('/assessment/projects-peer', [ProjectController::class, 'getProjectsWithAssessmentsPeer']);
    
            Route::get('/kelola-proyek', [KelolaProyekController::class, 'KelolaProyekView'])->name('kelola.proyek');
            Route::post('/tambah-proyek', [KelolaProyekController::class, 'AddProyek'])->name('kelola-proyek.store');
    
            Route::get('/kelola-kelompok', [KelolaKelompokController::class, 'KelolaKelompok'])->name('KelolaKelompok');
            Route::get('/kelola-kelompok/profile-mhs', [KelolaKelompokController::class, 'ProfileMhs'])->name('ProfileMhs');
            Route::get('/kelola-kelompok/create', [KelolaKelompokController::class, 'CreateKelompok'])->name('CreateKelompok');
            Route::get('/kelola-kelompok/kelompok/{id}', [KelolaKelompokController::class, 'showDetail'])->name('DetailKelompok');
            Route::get('/kelola-kelompok/export', [KelolaKelompokController::class, 'exportTemplate'])->name('kelola-kelompok.export');
            Route::post('/kelola-kelompok/import', [KelolaKelompokController::class, 'importData'])->name('kelola-kelompok.import');
    
            Route::get('/feedback', [FeedbackController::class, 'feedback'])->name('feedback');
    
            Route::get('/AnswerSelf', [AnswerController::class, 'answerSelf'])->name('dosen.answerSelf');
            Route::get('/Answers-self', [AnswerController::class, 'showAnswersSelf'])->name('showself');
            Route::get('/AnswerPeer', [AnswerController::class, 'answerPeer'])->name('dosen.answerPeer');
            Route::get('/answers-self-assessment', [AnswerController::class, 'getListAnswersView']);
            Route::get('/answers-peer-assessment', [AnswerController::class, 'getListAnswersPeerView']);
            Route::get('/answer-list-peer', [AnswerController::class, 'getListAnswerPeer'])->name('ListAnswerPeer');
            Route::get('/answers/details', [AnswerController::class, 'getDetails']);
    
            // User Management Mahasiswa
            Route::get('/manage-mahasiswa', [UserManagementController::class, 'ManageMahasiswa'])->name('dosen.manage-mahasiswa');
            Route::get('/manage-mahasiswa/input', [UserManagementController::class, 'InputMahasiswa'])->name('InputMahasiswa');
            Route::get('/manage-mahasiswa/detail', [UserManagementController::class, 'DetailMahasiswa'])->name('DetailMahasiswa');
            Route::get('/manage-mahasiswa/export', [UserManagementController::class, 'ExportMahasiswa'])->name('ExportMahasiswa');
            Route::post('/manage-mahasiswa/importDosen', [UserManagementController::class, 'ImportMahasiswa'])->name('ImportMahasiswa');
    
            // User Management Dosen
            Route::get('/manage-dosen', [UserManagementController::class, 'ManageDosen'])->name('ManageDosen');
            Route::get('/manage-dosen/input', [UserManagementController::class, 'InputDosen'])->name('InputDosen');
            Route::get('/manage-dosen/detail', [UserManagementController::class, 'DetailDosen'])->name('DetailDosen');
            Route::get('/manage-dosen/export', [UserManagementController::class, 'ExportDosen'])->name('ExportDosen');
            Route::post('/manage-dosen/import', [UserManagementController::class, 'ImportDosen'])->name('ImportDosen');
    
            //REPORT
            Route::get('/report', [ReportController::class, 'report'])->name('report');
            Route::get('/kelompok/report-detail', [ReportController::class, 'getScoreKelompok']);
    
            //Feedback
            Route::get('/feedback-detail', [FeedbackController::class, 'feedbackDetailView']);
            Route::post('/feedbacks-store-dosen', [FeedbackController::class, 'storeFeedback']);
        });
    
        //Route untuk Mahasiswa
        Route::middleware('role:mahasiswa')->prefix('mahasiswa')->group(function () {
            Route::get('/dashboard', [DashboardMahasiswa::class, 'dashboard'])->name('mahasiswa.dashboard');
            Route::get('/assessment/self', [AssessmentMahasiswa::class, 'selfAssessment'])->name('mahasiswa.assessment.self');
            Route::get('/assessment/peer', [AssessmentMahasiswa::class, 'peerAssessment'])->name('mahasiswa.assessment.peer');
            Route::get('/assessment/self-assessment', [SelfAssessment::class, 'assessment'])->name('mahasiswa.self.assessment');
            Route::get('/assessment/peer-assessment', [PeerAssessment::class, 'PeerAssessment'])->name('mahasiswa.peer.assessment');
            Route::get('/feedback', [FeedbackMahasiswa::class, 'feedbackMahasiswa'])->name('mahasiswa.feedback');
            Route::get('/report', [ReportMahasiswa::class, 'reportMahasiswa'])->name('mahasiswa.report');
            Route::get('/user-info', [SelfAssessment::class, 'getUserInfo'])->name('mahasiswa.info-student');
            Route::post('/peer-assessment/answers', [AssessmentMahasiswa::class, 'AnswersPeer']);
            Route::get('/peer-assessment/answers/{userId}', [AssessmentMahasiswa::class, 'getUserPeerAnswers']);
            Route::get('/peer-assessment/peer-detail', [DetailPeerMahasiswa::class, 'showDetail']);
            Route::get('/peer-assessment/self-detail', [DetailSelfMahasiswa::class, 'showDetail']);
            // Route::get('/notifications-mhs', [NotificationMahasiswa::class, 'index'])->name('index');
    
            Route::get('/profile', [ProfileMahasiswa::class, 'profile'])->name('profile');
    
            Route::get('/projects', [ProjectController::class, 'getUserProjects']);
            Route::get('/assessment-status', [ProjectController::class, 'getAssessmentStatus']);
            route::get('/project/report-detail', [ReportMahasiswa::class, 'getReportScoreView']);
    
            //-----------REPORT-------------//
            Route::get('/project-score-details', [ReportMahasiswa::class, 'getProjectScoreDetailsView']);
    
            // notif
            Route::get('/notifications-mhs', [NotificationMahasiswa::class, 'index'])->name('notifications.index');
            Route::post('/notifications/{id}/mark-as-read', [NotificationMahasiswa::class, 'markAsRead'])->name('notifications.markAsRead');
            Route::post('/notifications/mark-all-read', [NotificationMahasiswa::class, 'markAllAsRead'])->name('notifications.markAllAsRead');
    
            Route::get('/test-email', function() {
                $user = Auth::user();
                
                if (!$user) {
                    return "Please login first!";
                }
                
                try {
                    $notificationController = new NotificationMahasiswa();
                    $result = $notificationController->getNotifications();
                    
                    $responseData = json_decode($result->getContent());
                    
                    if ($responseData->success) {
                        return "Notifications processed successfully! Check your email inbox (and spam folder).";
                    } else {
                        return "Error processing notifications: " . ($responseData->message ?? 'Unknown error');
                    }
                } catch (\Exception $e) {
                    return "Error: " . $e->getMessage();
                }
            });
    
            Route::get('/feedback-details', [FeedbackMahasiswa::class, 'getFeedbackDetailView']);
        });
    
        //Route untuk admin
        Route::middleware('role:admin')->prefix('admin')->group(function () {
            Route::get('/dashboard', [DashboardAdminController::class, 'dashboard'])->name('dashboard.admin');
    
            //admin manage major
            Route::get('/ManageMajor', [MajorAdminController::class, 'createMajor']);
            Route::get('/ManageProdi', [MajorAdminController::class, 'showManageProdi']);
    
            //admin manage user
            Route::get('/ManageDosen', [UserAdminController::class, 'showManageDosen']);
            Route::get('/ManageMahasiswa', [UserAdminController::class, 'showManageMahasiswa'])->name('admin.ManageMahasiswa');
            Route::get('/manage-dosen/input', [UserAdminController::class, 'InputDosen'])->name('admin.InputDosen');
            Route::get('/manage-dosen/export', [UserAdminController::class, 'ExportDosen']);
            Route::post('/manage-dosen/import', [UserAdminController::class, 'ImportDosen']);
            Route::get('/manage-mahasiswa/input', [UserAdminController::class, 'InputMahasiswa'])->name('admin.InputMahasiswa');
            Route::get('/manage-mahasiswa/export', [UserAdminController::class, 'ExportMahasiswa']);
            Route::post('/manage-mahasiswa/import', [UserAdminController::class, 'ImportMahasiswa']);
        });
    });    
});