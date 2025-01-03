<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/login', [AuthController::class, 'login']);
Route::middleware(['auth:sanctum'])->put('/logout', [AuthController::class, 'logout']);

// Route::post('/login', [Auth::class,'login']);
// Route::middleware([ 'auth:sanctum' ])->group( function(){
//     Route::put('/logout', [Auth::class,'logout']);