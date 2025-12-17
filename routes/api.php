<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\{
    AuthController,
    HouseController,
    PaymentChannelController,
    ActivityController,
    BillingController,
    CitizenAcceptanceController,
    CitizenMessageController,
    DuesTypeController,
    TransactionController,
    UserController,
    AnnouncementController,
    MutationController,
    FamilyController,
    CitizensController,
    DashboardController,
    TransactionCategoriesController
};

/* Auth (public) */
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/auth/login-face', [AuthController::class, 'loginFace']);
Route::post('/register', [AuthController::class, 'register'])->name('register');
Route::post('/refresh-token', [AuthController::class, 'refreshToken']);

/* Public options */
Route::get('/houses/options', [HouseController::class, 'options']);

/* Protected (JWT) */
Route::middleware('jwt.auth')->group(function () {

    /* Auth */
    Route::post('/logout', [AuthController::class, 'logout']);

    /* Payment Channels */
    Route::get('/payment-channels', [PaymentChannelController::class, 'index']);
    Route::get('/payment-channels/{id}', [PaymentChannelController::class, 'show']);
    Route::post('/payment-channels', [PaymentChannelController::class, 'store']);

    /* Activities */
    Route::get('/activities', [ActivityController::class, 'index']);
    Route::post('/activities', [ActivityController::class, 'store']);

    /* Announcements */
    Route::get('/announcements', [AnnouncementController::class, 'index']);
    Route::post('/announcements', [AnnouncementController::class, 'store']);

    /* Users */
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);

    /* Mutations */
    Route::get('/mutations', [MutationController::class, 'index']);
    Route::post('/mutations', [MutationController::class, 'store']);

    /* Families */
    Route::get('/families', [FamilyController::class, 'index']);
    Route::get('/families/options', [FamilyController::class, 'options']);

    /* Citizen Verification */
    Route::get('/citizens/verification-list', [CitizenAcceptanceController::class, 'index']);

    /* Finance Report */
    Route::get('/finance/report', [TransactionController::class, 'report']);

    /* Houses */
    Route::get('/houses', [HouseController::class, 'index']);
    Route::post('/houses', [HouseController::class, 'store']);

    /* Citizens */
    Route::get('/citizens', [CitizensController::class, 'index']);
    Route::post('/citizens', [CitizensController::class, 'store']);

    /* Dashboard */
    Route::get('/dashboard/main', [DashboardController::class, 'mainDashboard']);
    Route::get('/dashboard/finance', [DashboardController::class, 'financeDashboard']);
    Route::get('/dashboard/activity', [DashboardController::class, 'activityDashboard']);
    Route::get('/dashboard/population', [DashboardController::class, 'populationDashboard']);

    /* Aspirasi */
    Route::get('/aspirasi', [CitizenMessageController::class, 'index']);
    Route::post('/aspirasi', [CitizenMessageController::class, 'store']);
    Route::get('/aspirasi/{id}', [CitizenMessageController::class, 'show']);
    Route::put('/aspirasi/{id}', [CitizenMessageController::class, 'update']);
    Route::delete('/aspirasi/{id}', [CitizenMessageController::class, 'destroy']);

    /* Dues Types */
    Route::get('/dues-types', [DuesTypeController::class, 'index']);
    Route::post('/dues-types', [DuesTypeController::class, 'store']);
    Route::get('/dues-types/{id}', [DuesTypeController::class, 'show']);
    Route::put('/dues-types/{id}', [DuesTypeController::class, 'update']);
    Route::delete('/dues-types/{id}', [DuesTypeController::class, 'destroy']);

    /* Billings */
    Route::get('/billings', [BillingController::class, 'index']);
    Route::post('/billings', [BillingController::class, 'store']);
    Route::get('/billings/{id}', [BillingController::class, 'show']);
    Route::put('/billings/{id}', [BillingController::class, 'update']);
    Route::delete('/billings/{id}', [BillingController::class, 'destroy']);

    /* Other Income */
    Route::get('/other-incomes', [TransactionController::class, 'indexIncome']); 
    Route::get('/other-expenses', [TransactionController::class, 'indexExpense']);      
    Route::post('/other-incomes', [TransactionController::class, 'storeIncome']);
    Route::post('/other-expenses', [TransactionController::class, 'storeExpense']);
    Route::get('/other-incomes/{id}', [TransactionController::class, 'showIncome']);
    Route::put('/other-incomes/{id}', [TransactionController::class, 'updateIncome']);
    Route::delete('/other-incomes/{id}', [TransactionController::class, 'destroyIncome']);

    // Transaction Categories
    Route::get('/transaction-categories', [TransactionCategoriesController::class, 'option']);
});

/* 404 */
Route::fallback(function () {
    return response()->json(['message' => 'Not Found'], 404);
});
