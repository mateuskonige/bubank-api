<?php

use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TransactionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json(["version" => "Laravel v" . Illuminate\Foundation\Application::VERSION]);
});

Route::prefix('auth')->middleware('throttle:global')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::get('user', [AuthController::class, 'me']);
    Route::post('forgot-password', [AuthController::class, 'forgot_password'])->name('password.email');
    Route::post('reset-password', [AuthController::class, 'reset_password'])->name('password.reset');
});

Route::middleware(['auth', 'throttle:auth'])->group(function () {
    Route::get('accounts', [AccountController::class, 'index'])->name('accounts.index');
    Route::delete('accounts/{account}', [AccountController::class, 'destroy'])->name('accounts.destroy');
    Route::post('accounts/{id}/restore', [AccountController::class, 'restore'])->name('accounts.restore');

    Route::apiResource('transactions', TransactionController::class)->except('update', 'destroy');
    Route::get('/transactions/{id}/status', [TransactionController::class, 'status'])->name('transactions.status');
});
