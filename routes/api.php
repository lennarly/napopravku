<?php

use App\Http\Controllers\Api\FileController;
use App\Http\Controllers\Api\FolderController;
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
    Route::get('/list', [FileController::class, 'list']);
    Route::delete('/files', [FileController::class, 'remove']);

    Route::post('/folders', [FolderController::class, 'add']);
});
