<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Modules\Auth\Controllers\AuthController;

use App\Modules\Files\Controllers\FileController;

Route::prefix('v1')->group(function () {
    // Auth Routes
    Route::prefix('auth')->middleware('throttle:6,1')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
        Route::post('/resend-otp', [AuthController::class, 'resendOtp']);
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('/reset-password', [AuthController::class, 'resetPassword']);
        Route::post('/register-rollback', [AuthController::class, 'rollbackRegistration']);
    });

    // Protected Routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);

        // Admin Module
        Route::prefix('admin')->middleware('role:SUPER_ADMIN|ADMIN')->group(function () {
            Route::get('/stats', [\App\Modules\Admin\Controllers\AdminController::class, 'stats']);
            Route::get('/files', [\App\Modules\Admin\Controllers\AdminController::class, 'files']);
            Route::get('/audit-logs', [\App\Modules\Admin\Controllers\AdminController::class, 'auditLogs']);
            Route::post('/create-admin', [\App\Modules\Admin\Controllers\AdminController::class, 'createAdmin']);
        });

        // Dashboard Module
        Route::middleware('role:SUPER_ADMIN|ADMIN')->group(function () {
            Route::get('/dashboard/statistics', [\App\Modules\Dashboard\Controllers\DashboardController::class, 'index']);
        });

        // Users Management Module
        Route::get('/users', [\App\Modules\Users\Controllers\UserController::class, 'index']);
        Route::put('/users/{id}/ban', [\App\Modules\Users\Controllers\UserController::class, 'ban']);
        Route::put('/users/{id}/unban', [\App\Modules\Users\Controllers\UserController::class, 'unban']);
        Route::put('/users/{id}/suspend', [\App\Modules\Users\Controllers\UserController::class, 'suspend']);
        Route::put('/users/{id}/unsuspend', [\App\Modules\Users\Controllers\UserController::class, 'unsuspend']);



        // Files
        Route::get('/files', [FileController::class, 'index']);
        Route::post('/files', [FileController::class, 'store']);
        Route::post('/files/sync', [FileController::class, 'sync']);
        Route::post('/files/sync-delete', [FileController::class, 'syncDelete']);
        Route::put('/files/{id}', [FileController::class, 'update']);
        Route::delete('/files/{id}', [FileController::class, 'destroy']);

        // Subscription
        Route::post('/subscription/upgrade', [\App\Modules\Subscription\Controllers\SubscriptionController::class, 'requestUpgrade']);

        // Dashboard
        Route::get('/dashboard', [\App\Modules\Dashboard\Controllers\DashboardController::class, 'index']);
    });
});