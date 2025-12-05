<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\HouseController;
use App\Http\Controllers\Api\PaymentChannelController;
use App\Http\Controllers\Api\ActivityController;
use App\Http\Controllers\Api\CitizenAcceptanceController;
use App\Http\Controllers\Api\CitizenMessageController;
use App\Http\Controllers\Api\UserController;

Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/register', [AuthController::class, 'register'])->name('register');
Route::post('/refresh-token', [AuthController::class, 'refreshToken']);
Route::get('/houses/options', [HouseController::class, 'options']);

Route::middleware('jwt.auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/payment-channels', [PaymentChannelController::class, 'index']);
    Route::get('/payment-channels/{id}', [PaymentChannelController::class, 'show']);
    Route::post('/payment-channels', [PaymentChannelController::class, 'store']);

    Route::get('/activities', [ActivityController::class, 'index']);
    Route::post('/activities', [\App\Http\Controllers\Api\ActivityController::class, 'store']);

    Route::get('/announcements', [\App\Http\Controllers\Api\AnnouncementController::class, 'index']);
    Route::post('/announcements', [\App\Http\Controllers\Api\AnnouncementController::class, 'store']);

    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);

    Route::get('/mutations', [\App\Http\Controllers\Api\MutationController::class, 'index']);
    Route::post('/mutations', [\App\Http\Controllers\Api\MutationController::class, 'store']);
    Route::get('/families/options', [\App\Http\Controllers\Api\FamilyController::class, 'options']);

    // KEUANGAN (TRANSAKSI)
    Route::get('/finance/incomes', [\App\Http\Controllers\Api\TransactionController::class, 'incomes']);
    Route::get('/finance/expenses', [\App\Http\Controllers\Api\TransactionController::class, 'expenses']);

    // LAPORAN
    Route::get('/finance/report', [\App\Http\Controllers\Api\TransactionController::class, 'report']);

    // Endpoint List Warga
    Route::get('/citizens/verification-list', [CitizenAcceptanceController::class, 'index']);

    // Aspirasi Warga Routes
    Route::get('/aspirasi', [CitizenMessageController::class, 'index']);
    Route::post('/aspirasi', [CitizenMessageController::class, 'store']);
    Route::get('/aspirasi/{id}', [CitizenMessageController::class, 'show']);
    Route::put('/aspirasi/{id}', [CitizenMessageController::class, 'update']);
    Route::delete('/aspirasi/{id}', [CitizenMessageController::class, 'destroy']);
});

Route::fallback(function () {
    return response()->view('errors.404', [], 404);
});
