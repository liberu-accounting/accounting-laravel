<?php

use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\PlaidController;
use App\Http\Controllers\Api\PlaidWebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::apiResource('transactions', TransactionController::class);

    Route::get('/exchange-rates', function () {
        return app(App\Services\ExchangeRateService::class)->getLatestRates();
    })->middleware('throttle:60,1');

    // Plaid API Routes
    Route::prefix('plaid')->middleware('throttle:60,1')->group(function () {
        Route::post('/create-link-token', [PlaidController::class, 'createLinkToken']);
        Route::post('/store-connection', [PlaidController::class, 'storeConnection']);
        Route::get('/connections', [PlaidController::class, 'listConnections']);
        Route::post('/connections/{connection}/sync', [PlaidController::class, 'syncTransactions'])->middleware('throttle:10,1');
        Route::get('/connections/{connection}/balances', [PlaidController::class, 'getBalances'])->middleware('throttle:30,1');
        Route::delete('/connections/{connection}', [PlaidController::class, 'removeConnection']);
    });
});

// Plaid Webhook (public endpoint, no auth required)
Route::post('/webhooks/plaid', [PlaidWebhookController::class, 'handle']);