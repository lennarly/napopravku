<?php

use App\Http\Controllers\Api\FileController;
use App\Http\Controllers\Api\FolderController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;

// Auth

Route::prefix('auth')
    ->controller(AuthController::class)
    ->group(function () {
        Route::post('/register', 'register');
        Route::post('/login', 'login');
    });

// User

Route::middleware('auth:sanctum')
    ->controller(UserController::class)
    ->group(function () {
        Route::get('/user', 'info');
    });

// Files

Route::middleware('auth:sanctum')
    ->controller(FileController::class)
    ->group(function () {
        Route::post('/files', 'upload');
        Route::get('/files', 'list');
        Route::put('/files', 'edit');
        Route::delete('/files', 'remove');
        Route::get('/files/generate', 'generate');
        Route::get('/files/download', 'download');
    });

// Folders

Route::middleware('auth:sanctum')
    ->controller(FolderController::class)
    ->group(function () {
        Route::post('/folders', 'add');
        Route::get('/folders', 'info');
    });

Route::controller(FolderController::class)
    ->group(function () {
        Route::get('/folders/all', 'stats');
    });

