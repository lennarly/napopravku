<?php

use App\Http\Controllers\Api\FileController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;

// Auth

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

// User

Route::middleware('auth:sanctum')->prefix('user')->group(function () {
    Route::get('/info', [UserController::class, 'info']);
});


// Storage

Route::middleware('auth:sanctum')->prefix('storage')->group(function () {
    Route::post('/upload', [FileController::class, 'upload']);
});
