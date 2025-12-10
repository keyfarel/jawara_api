<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\HouseController;
use App\Http\Controllers\Api\PaymentChannelController;
use App\Http\Controllers\Api\ActivityController;
use App\Http\Controllers\Api\BillingController;
use App\Http\Controllers\Api\CitizenAcceptanceController;
use App\Http\Controllers\Api\CitizenMessageController;
use App\Http\Controllers\Api\DuesTypeController;
use App\Http\Controllers\Api\TransactionController;
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

    // KELUARGA
    Route::get('/families', [\App\Http\Controllers\Api\FamilyController::class, 'index']);
    Route::get('/families/options', [\App\Http\Controllers\Api\FamilyController::class, 'options']);

    // Endpoint List Warga
    Route::get('/citizens/verification-list', [CitizenAcceptanceController::class, 'index']);

    // LAPORAN
    Route::get('/finance/report', [\App\Http\Controllers\Api\TransactionController::class, 'report']);

    // Rumah
    // Route::resource('/houses', HouseController::class);
    Route::get('/houses', [HouseController::class, 'index']);
    Route::post('/houses', [HouseController::class, 'store']);

    // Warga
    Route::get('/citizens', [\App\Http\Controllers\Api\CitizensController::class, 'index']);
    Route::post('/citizens', [\App\Http\Controllers\Api\CitizensController::class, 'store']);
    
    // DASHBOARD
    Route::get('/dashboard/main', [\App\Http\Controllers\Api\DashboardController::class, 'mainDashboard']);
    Route::get('/dashboard/finance', [\App\Http\Controllers\Api\DashboardController::class, 'financeDashboard']);
    route::get('/dashboard/activity', [\App\Http\Controllers\Api\DashboardController::class, 'activityDashboard']);
    Route::get('/dashboard/population', [\App\Http\Controllers\Api\DashboardController::class, 'populationDashboard']);
    // Aspirasi Warga Routes
    Route::get('/aspirasi', [CitizenMessageController::class, 'index']);
    Route::post('/aspirasi', [CitizenMessageController::class, 'store']);
    Route::get('/aspirasi/{id}', [CitizenMessageController::class, 'show']);
    Route::put('/aspirasi/{id}', [CitizenMessageController::class, 'update']);
    Route::delete('/aspirasi/{id}', [CitizenMessageController::class, 'destroy']);

    // Jenis Iuran Routes
    Route::get('/dues-types', [DuesTypeController::class, 'index']);
    Route::post('/dues-types', [DuesTypeController::class, 'store']);
    Route::get('/dues-types/{id}', [DuesTypeController::class, 'show']);
    Route::put('/dues-types/{id}', [DuesTypeController::class, 'update']);
    Route::delete('/dues-types/{id}', [DuesTypeController::class, 'destroy']);

    // Fitur Tagihan (Billings)
    Route::get('/billings', [BillingController::class, 'index']);
    Route::post('/billings', [BillingController::class, 'store']);
    Route::get('/billings/{id}', [BillingController::class, 'show']);
    Route::put('/billings/{id}', [BillingController::class, 'update']);
    Route::delete('/billings/{id}', [BillingController::class, 'destroy']);

    // ROUTES CRUD PEMASUKAN LAIN (Other Incomes)
    Route::get('/other-incomes', [TransactionController::class, 'indexIncome']);       // GET List (Income)
    Route::get('/other-expenses', [TransactionController::class, 'indexExpense']);       // GET List (Expense)
    Route::post('/other-incomes', [TransactionController::class, 'storeIncome']);      // POST Create
    Route::get('/other-incomes/{id}', [TransactionController::class, 'showIncome']);   // GET Detail
    Route::put('/other-incomes/{id}', [TransactionController::class, 'updateIncome']); // PUT Update
    Route::delete('/other-incomes/{id}', [TransactionController::class, 'destroyIncome']); // DELETE Remove
});

Route::fallback(function () {
    return response()->view('errors.404', [], 404);
});