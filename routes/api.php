<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Dosen\SelfAssessmentController;
use App\Http\Controllers\Dosen\PeerAssessmentController;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function () {
        return auth()->user();
    });
    Route::put('/logout', [AuthController::class, 'logout']);

    Route::get('/export-self-assessment', [SelfAssessmentController::class, 'exportExcel']);
    Route::get('/export-peer-assessment', [PeerAssessmentController::class, 'exportExcel']);
    Route::post('/import-self-assessment', [SelfAssessmentController::class, 'import']);

});

