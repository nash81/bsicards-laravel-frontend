<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\CardController;
use App\Http\Controllers\Api\DepositController;
use App\Http\Controllers\Api\TransactionController;

/*
|--------------------------------------------------------------------------
| Mobile App API Routes  (prefix: /api/v1)
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // ------------------------------------------------------------------
    // Public – Authentication
    // ------------------------------------------------------------------
    Route::prefix('auth')->group(function () {
        Route::post('login',    [AuthController::class, 'login']);
        Route::post('register', [AuthController::class, 'register']);
    });

    // ------------------------------------------------------------------
    // Protected – Sanctum token required
    // ------------------------------------------------------------------
    Route::middleware('auth:sanctum')->group(function () {

        // Auth
        Route::prefix('auth')->group(function () {
            Route::post('logout',          [AuthController::class, 'logout']);
            Route::get('me',               [AuthController::class, 'me']);
            Route::post('change-password', [AuthController::class, 'changePassword']);
        });

        // Profile & Balance
        Route::prefix('profile')->group(function () {
            Route::get('/',                    [ProfileController::class, 'index']);
            Route::post('update',              [ProfileController::class, 'update']);
            Route::get('balance',              [ProfileController::class, 'balance']);
            Route::get('recent-transactions',  [ProfileController::class, 'recentTransactions']);
        });

        // Transactions
        Route::prefix('transactions')->group(function () {
            Route::get('/',           [TransactionController::class, 'index']);
            Route::get('deposits',    [TransactionController::class, 'deposits']);
            Route::get('withdrawals', [TransactionController::class, 'withdrawals']);
            Route::get('{tnx}',       [TransactionController::class, 'show']);
        });

        // Deposits / Payment Gateways
        Route::prefix('deposit')->group(function () {
            Route::get('gateways',                [DepositController::class, 'gateways']);
            Route::post('initiate',               [DepositController::class, 'initiate']);
            Route::post('manual-proof',           [DepositController::class, 'submitManualProof']);
            Route::get('status/{tnx}',            [DepositController::class, 'status']);
        });

        // Virtual Cards – MasterCard
        Route::prefix('cards/master')->group(function () {
            Route::get('/',                       [CardController::class, 'masterList']);
            Route::get('{cardId}',                [CardController::class, 'masterView']);
            Route::post('load',                   [CardController::class, 'masterLoadFunds']);
            Route::post('{cardId}/block',         [CardController::class, 'masterBlock']);
            Route::post('{cardId}/unblock',       [CardController::class, 'masterUnblock']);
        });

        // Virtual Cards – Visa
        Route::prefix('cards/visa')->group(function () {
            Route::get('/',                       [CardController::class, 'visaList']);
            Route::get('{cardId}',                [CardController::class, 'visaView']);
            Route::post('load',                   [CardController::class, 'visaLoadFunds']);
            Route::post('{cardId}/block',         [CardController::class, 'visaBlock']);
        });

        // Virtual Cards – Digital Mastercard
        Route::prefix('cards/digital')->group(function () {
            Route::get('/',                       [CardController::class, 'digitalList']);
            Route::post('apply',                  [CardController::class, 'digitalApply']);
            Route::post('addon',                  [CardController::class, 'digitalAddon']);
            Route::get('{cardId}',                [CardController::class, 'digitalView']);
            Route::post('load',                   [CardController::class, 'digitalLoadFunds']);
            Route::post('{cardId}/block',         [CardController::class, 'digitalBlock']);
            Route::get('{cardId}/check-3ds',      [CardController::class, 'check3ds']);
            Route::post('{cardId}/approve-3ds',   [CardController::class, 'approve3ds']);
            Route::get('{cardId}/wallet-otp',     [CardController::class, 'checkWalletOtp']);
        });

    });

});
