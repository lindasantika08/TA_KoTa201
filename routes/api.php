<?php

use App\Http\Controllers\Admin\MajorAdminController;
use App\Http\Controllers\Admin\UserAdminController;
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
use App\Http\Controllers\Dosen\SelfAssessmentDosen;
use App\Http\Controllers\Dosen\PeerAssessmentDosen;
use App\Http\Controllers\Dosen\ProfileController;
use App\Http\Controllers\Dosen\FeedbackController;

use App\Http\Controllers\Mahasiswa\AssessmentMahasiswa;
use App\Http\Controllers\Mahasiswa\DashboardMahasiswa;
use App\Http\Controllers\Mahasiswa\SelfAssessment;
use App\Http\Controllers\Mahasiswa\PeerAssessment;
use App\Http\Controllers\Mahasiswa\DetailSelfMahasiswa;
use App\Http\Controllers\Mahasiswa\DetailPeerMahasiswa;
use App\Http\Controllers\Mahasiswa\FeedbackMahasiswa;
use App\Http\Controllers\Mahasiswa\ReportMahasiswa;
use App\Http\Controllers\Mahasiswa\ProfileMahasiswa;
use App\Http\Controllers\Mahasiswa\NotificationMahasiswa;
use Illuminate\Support\Facades\Auth;
// use SebastianBergmann\CodeCoverage\Report\Html\Dashboard;

Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->get('/validate-token', [AuthController::class, 'validateToken']);
Route::middleware('auth:sanctum')->get('/user/status', [AuthController::class, 'getStatus']);
Route::middleware('auth:sanctum')->post('/change-password', [AuthController::class, 'changePassword']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

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
    Route::get('/prodis-by-major', [KelolaProyekController::class, 'getProdisByMajor']);

    Route::get('/projects', [KelolaProyekController::class, 'getProjects']);
    Route::get('/project-dropdown', [ProjectController::class, 'index']);
    Route::get('/get-question-id', [AnswerController::class, 'getQuestionId']);
    Route::get('/answers/list', [AnswerController::class, 'getListAnswers']);
    Route::get('/answersPeer/list', [AnswerController::class, 'getListAnswersPeer']);
    Route::get('/answersKelompokPeer/list', [AnswerController::class, 'getListAnswersKelompokPeer']);
    Route::get('/assessment/projects', [ProjectController::class, 'getProjectsWithAssessments']);
    Route::get('/proyek-self-assessment', [ProjectController::class, 'getDataSelf']);
    Route::get('/proyek-Peer-assessment', [ProjectController::class, 'getDataPeer']);
    Route::post('/toggle-publish-assessment', [ProjectController::class, 'togglePublishAssessment']);
    Route::post('/toggle-publish-assessment-peer', [ProjectController::class, 'togglePublishAssessmentPeer']);
    Route::get('/projects/active', [DashboardDosen::class, 'getActiveProjects']);

    Route::get('/data-with-bobot-self', [AssessmentController::class, 'getAssessmentsWithBobotSelf']);


    // self assessment dosen
    Route::get('/questions-dosen', [SelfAssessmentDosen::class, 'getQuestionsByProject']);
    Route::get('/user-info-dosen', [SelfAssessmentDosen::class, 'getUserInfoDosen']);
    Route::post('/save-answer', [SelfAssessmentDosen::class, 'saveAnswer']);
    Route::get('/get-answer/{questionId}', [SelfAssessmentDosen::class, 'getAnswer']);
    Route::post('/save-all-answers-dosen', [SelfAssessmentDosen::class, 'saveAllAnswers']);

    // peer assessment dosen
    Route::get('/questions-peer-dosen', [PeerAssessmentDosen::class, 'getQuestionsByProjectPeer']);
    Route::post('/save-answer-peer-dosen', [PeerAssessmentDosen::class, 'saveAnswerPeer']);
    Route::post('/save-all-answers-peer-dosen', [PeerAssessmentDosen::class, 'saveAllAnswersPeerDosen']);
    Route::get('/get-answer-peer-dosen/{questionId}', [PeerAssessmentDosen::class, 'getAnswerPeer']);

    // Manage Group
    Route::get('/kelola-kelompok/export', [KelolaKelompokController::class, 'exportTemplate']);
    Route::post('/kelola-kelompok/import', [KelolaKelompokController::class, 'importData']);
    Route::get('/kelola-kelompok/get-profile/{user_id}', [KelolaKelompokController::class, 'getProfile']);
    Route::get('/kelola-kelompok/get-angkatan', [KelolaKelompokController::class, 'getAngkatan']);

    Route::get('/users/search', [AnswerController::class, 'searchByNip']);
    Route::get('/kelompok-dosen', [AnswerController::class, 'getKelompokDosen']);
    Route::get('/answered-peers-dosen', [AnswerController::class, 'answeredPeersDosen']);
    Route::get('/answers/statistics', [AnswerController::class, 'getStatistics']);
    Route::get('/answers/statistics-dashboard', [DashboardDosen::class, 'getStatistics']);
    Route::get('/answers/statistics-peer', [AnswerController::class, 'getStatisticsPeer']);
    Route::get('projects/active', [DashboardDosen::class, 'getActiveProjects']);
    Route::post('/changeStatus', [ProjectController::class, 'changeStatus']);
    Route::get('/answers/get-details', [AnswerController::class, 'getDetailsAnswer']);
    Route::get('/answers/{id}', [AnswerController::class, 'showdetail']);

    //Manage User Dosen dan Mahasiswa (Role Dosen)
    Route::get('/get-mahasiswa', [UserManagementController::class, 'getMahasiswa']);
    Route::get('/get-dosen', [UserManagementController::class, 'getDosen']);
    Route::get('/get-dosen/detail/{user_id}', [UserManagementController::class, 'getProfileDosen']);
    Route::get('/get-jurusan', [UserManagementController::class, 'getJurusanList']);
    Route::get('/get-prodi/{majorId}', [UserManagementController::class, 'getProdiByMajor']);
    Route::get('/get-angkatan', [UserManagementController::class, 'getAngkatan']);
    Route::get('/get-class', [UserManagementController::class, 'getClass']);
    Route::get('/get-majors', [UserManagementController::class, 'getMajor']);

    Route::get('/assessment/projects', [ProjectController::class, 'getProjectsWithAssessments']);
    Route::get('/proyek-self-assessment', [ProjectController::class, 'getDataSelf']);
    Route::get('/proyek-self-assessment-grouped', [ProjectController::class, 'getDataSelfGrouped']);
    Route::post('/save-all-answers', [AssessmentController::class, 'saveAllAnswers']);

    //Dosen Profile
    Route::get('/get-profile-dosen', [ProfileController::class, 'getProfile']);
    Route::post('/dosen/upload-profile-photo', [ProfileController::class, 'uploadProfilePhoto']);
    Route::put('/dosen/update-profile', [ProfileController::class, 'updateProfile']);
    Route::delete('/dosen/delete-profile-photo', [ProfileController::class, 'deleteProfilePhoto']);

    //Dosen Report
    Route::get('/dropdown-options', [ReportController::class, 'getDropdownOptions']);
    Route::get('/kelompok/report', [ReportController::class, 'getKelompokReport']);
    Route::get('/kelompok/report-detail', [ReportController::class, 'getScoreKelompok']);
    Route::get('/report/kelompok/answers', [ReportController::class, 'getKelompokAnswers']);
    Route::post('/report/storeReport', [ReportController::class, 'storeReport']);
    Route::get('/student-peer-data', [ReportController::class, 'getStudentPeerData']);
    Route::get('/questions-peer-dosen-report', [ReportController::class, 'getQuestionsByProjectPeerReport']);

    //Dosen Feedback
    Route::get('/feedbacks-get-answer', [FeedbackController::class, 'getFeedbackAnswer']);
    Route::get('/feedback-summary', [FeedbackController::class, 'getSummaryFeedback']);
    Route::post('/feedbacks-store-dosen', [FeedbackController::class, 'storeFeedback']);


    //-------------------------------------mahasiswa------------------------------------------------//
    // dashboard mhs
    Route::get('/projects-user', [DashboardMahasiswa::class, 'getUserProject']);
    Route::get('/assessment-status', [DashboardMahasiswa::class, 'getSelfAssessmentStatus']);
    Route::get('/count-peer', [DashboardMahasiswa::class, 'getPeerAssessmentDetails']);

    // self assessment mhs
    Route::get('/self-assessment', [AssessmentMahasiswa::class, 'getDataSelf']);
    Route::get('/bobot', [SelfAssessment::class, 'getFilteredBobot']);
    Route::get('/questions', [SelfAssessment::class, 'getQuestionsByProject']);
    Route::post('/save-answer-mhs', [SelfAssessment::class, 'saveAnswer']);
    Route::get('/user-info', [SelfAssessment::class, 'getUserInfo']);
    Route::post('/save-all-answers', [SelfAssessment::class, 'saveAllAnswers']);
    Route::get('/get-answer-mhs/{questionId}', [SelfAssessment::class, 'getAnswer']);

    // assessment & project mhs
    Route::get('/bobot', [SelfAssessment::class, 'getFilteredBobot']);
    Route::get('/type-kriteria', [SelfAssessment::class, '']);
    Route::get('/proyek-self-assessment', [ProjectController::class, 'getDataSelf']);
    Route::get('/proyek-Peer-assessment', [ProjectController::class, 'getDataPeer']);

    // peer assessment mhs
    Route::get('/peer-assessment', [AssessmentMahasiswa::class, 'getDataPeer']);
    Route::get('/check-peer-data', [AssessmentMahasiswa::class, 'checkData']);
    Route::get('/questions-peer', [PeerAssessment::class, 'getQuestionsByProject']);
    Route::post('/save-answer-peer', [PeerAssessment::class, 'AnswersPeer']);
    Route::post('/save-all-answers-peer', [PeerAssessment::class, 'saveAllAnswersPeer']);
    Route::get('/existing-peer-answers', [PeerAssessment::class, 'getExistingPeerAnswers']);
    Route::get('/answered-peers', [PeerAssessment::class, 'answeredPeers']);
    Route::get('/get-answer-peer/{questionId}', [PeerAssessment::class, 'getAnswerPeer']);
    Route::get('/groups', [PeerAssessment::class, 'getPeerByGroup']);
    Route::get('/users/search', [PeerAssessment::class, 'searchByNim']);
    Route::get('/user-info-peer', [PeerAssessment::class, 'getUserInfo']);

    // detail assessment
    Route::get('/user-detail-answer', [DetailSelfMahasiswa::class, 'getUserInfo']);
    Route::get('/detail-answer-self', [DetailSelfMahasiswa::class, 'getAnswerSelf']);
    Route::get('/peer-assessment-detail', [DetailPeerMahasiswa::class, 'getAnswerPeer']);

    //mahasiswa Report
    Route::get('/mahasiswa/projects', [ReportMahasiswa::class, 'getProjects']);
    Route::get('/project-score-details', [ReportMahasiswa::class, 'getProjectScoreDetails']);
    Route::get('/project-feedback', [FeedbackMahasiswa::class, 'getFeedback']);

    //Mahasiswa Profile
    Route::get('/get-profile', [ProfileMahasiswa::class, 'getProfile']);
    Route::post('/mahasiswa/upload-profile-photo', [ProfileMahasiswa::class, 'uploadProfilePhoto']);
    Route::delete('/mahasiswa/delete-profile-photo', [ProfileMahasiswa::class, 'deleteProfilePhoto']);
    Route::put('/mahasiswa/update-profile', [ProfileMahasiswa::class, 'updateProfile']);


    Route::get('/assessments', [NotificationMahasiswa::class, 'getAssessments']);
    Route::get('/notifications', [NotificationMahasiswa::class, 'getNotifications']);
    Route::post('/notifications/{id}/mark-as-read', [NotificationMahasiswa::class, 'markAsRead']);
    Route::post('/assessment/{assessment}/notify', [NotificationMahasiswa::class, 'createAssessmentNotification']);

    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationMahasiswa::class, 'index']);
        Route::get('/get', [NotificationMahasiswa::class, 'getNotifications']);
        Route::post('/{id}/read', [NotificationMahasiswa::class, 'markAsRead']);
        Route::post('/read-all', [NotificationMahasiswa::class, 'markAllAsRead']);
        Route::post('/count', [NotificationMahasiswa::class, 'getCountNotif']);
    });

    //Mahasiswa Feedback
    Route::get('/mahasiswa/feedback', [FeedbackMahasiswa::class, 'getStudentAssessmentsStatus']);
    Route::get('/project/{projectId}/group-members', [FeedbackMahasiswa::class, 'getGroupMembers']);
    Route::post('/feedback/store', [FeedbackMahasiswa::class, 'saveFeedbackMahasiswa']);
    Route::get('/feedback/given', [FeedbackMahasiswa::class, 'getUserGivenFeedbacks']);

    //================ADMIN====================//

    //admin manage Major
    Route::get('/get-major', [MajorAdminController::class, 'showMajor']);
    Route::post('/add-major', [MajorAdminController::class, 'addMajor']);
    Route::post('/delete-major', [MajorAdminController::class, 'deleteMajor']);
    Route::post('/edit-major', [MajorAdminController::class, 'editMajor']);
    Route::get('/get-prodi', [MajorAdminController::class, 'showProdi']);
    Route::get('/get-major-forDropDown', [MajorAdminController::class, 'showMajorDropDown']);
    Route::post('/add-prodi', [MajorAdminController::class, 'addProdi']);
    Route::post('/delete-prodi', [MajorAdminController::class, 'deleteProdi']);
    Route::post('/update-prodi', [MajorAdminController::class, 'updateProdi']);

    //admin manage user
    Route::get('/get-dosen-admin', [UserAdminController::class, 'getDosen']);
    Route::post('/delete-dosen', [UserAdminController::class, 'deleteDosen']);
    Route::post('/update-dosen', [UserAdminController::class, 'editDosen']);
    Route::post('/delete-mahasiswa', [UserAdminController::class, 'deleteMahasiswa']);
    Route::post('/update-mahasiswa', [UserAdminController::class, 'editMahasiswa']);
});
