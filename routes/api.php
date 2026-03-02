<?php

use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\PlaidController;
use App\Http\Controllers\Api\PlaidWebhookController;
use App\Http\Controllers\Api\RevolutController;
use App\Http\Controllers\Api\RevolutWebhookController;
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

    // Revolut Business API Routes
    Route::prefix('revolut')->middleware('throttle:60,1')->group(function () {
        Route::get('/authorize', [RevolutController::class, 'redirectToRevolut']);
        Route::post('/callback', [RevolutController::class, 'handleCallback']);
        Route::get('/connections', [RevolutController::class, 'listConnections']);
        Route::get('/connections/{connection}/accounts', [RevolutController::class, 'getAccounts'])->middleware('throttle:30,1');
        Route::post('/connections/{connection}/sync', [RevolutController::class, 'syncTransactions'])->middleware('throttle:10,1');
        Route::post('/connections/{connection}/pay', [RevolutController::class, 'sendPayment'])->middleware('throttle:30,1');
        Route::post('/connections/{connection}/bulk-pay', [RevolutController::class, 'sendBulkPayment'])->middleware('throttle:10,1');
        Route::delete('/connections/{connection}', [RevolutController::class, 'removeConnection']);
    });
});

// Plaid OAuth redirect endpoint (public endpoint, no auth required)
Route::get('/plaid/oauth-redirect', [PlaidController::class, 'handleOAuthRedirect']);

// Plaid Webhook (public endpoint, no auth required)
Route::post('/webhooks/plaid', [PlaidWebhookController::class, 'handle']);

// Revolut Webhook (public endpoint, no auth required)
Route::post('/webhooks/revolut', [RevolutWebhookController::class, 'handle']);