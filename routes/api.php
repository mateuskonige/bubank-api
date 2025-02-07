<?php

use App\Http\Controllers\API\AccountController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\TransactionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json(["version" => "Laravel v" . Illuminate\Foundation\Application::VERSION]);
});

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::get('user', [AuthController::class, 'me']);
    Route::post('change-password', [AuthController::class, 'change_password']);
});

Route::middleware('auth')->group(function () {
    Route::apiResource('accounts', AccountController::class);
    Route::get('accounts/{account}/transactions', [AccountController::class, 'getTransactions']);

    Route::apiResource('transactions', TransactionController::class);
});
