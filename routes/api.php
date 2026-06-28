<?php

use App\Http\Controllers\Api\ChartOfAccountController;
use App\Http\Controllers\Api\GeneralLedgerController;
use App\Http\Controllers\Api\JournalEntryController;
use App\Http\Controllers\Api\PlaidController;
use App\Http\Controllers\Api\PlaidWebhookController;
use App\Http\Controllers\Api\QboController;
use App\Http\Controllers\Api\QboWebhookController;
use App\Http\Controllers\Api\RevolutController;
use App\Http\Controllers\Api\RevolutWebhookController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\WiseController;
use App\Http\Controllers\Api\WiseWebhookController;
use App\Services\ExchangeRateService;
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

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/user', fn (Request $request) => $request->user());

    Route::apiResource('transactions', TransactionController::class);

    // Core accounting REST endpoints (R7)
    Route::middleware('throttle:60,1')->group(function (): void {
        Route::apiResource('chart-of-accounts', ChartOfAccountController::class);
        Route::apiResource('journal-entries', JournalEntryController::class)
            ->only(['index', 'store', 'show', 'destroy']);
        Route::get('/general-ledger/trial-balance', [GeneralLedgerController::class, 'trialBalance']);
        Route::get('/general-ledger/balances', [GeneralLedgerController::class, 'balances']);
    });

    Route::get('/exchange-rates', fn () => app(ExchangeRateService::class)->getLatestRates())->middleware('throttle:60,1');

    // Plaid API Routes
    Route::prefix('plaid')->middleware('throttle:60,1')->group(function (): void {
        Route::post('/create-link-token', [PlaidController::class, 'createLinkToken']);
        Route::post('/store-connection', [PlaidController::class, 'storeConnection']);
        Route::get('/connections', [PlaidController::class, 'listConnections']);
        Route::post('/connections/{connection}/sync', [PlaidController::class, 'syncTransactions'])->middleware('throttle:10,1');
        Route::get('/connections/{connection}/balances', [PlaidController::class, 'getBalances'])->middleware('throttle:30,1');
        Route::delete('/connections/{connection}', [PlaidController::class, 'removeConnection']);
    });

    // Revolut Business API Routes
    Route::prefix('revolut')->middleware('throttle:60,1')->group(function (): void {
        Route::get('/authorize', [RevolutController::class, 'redirectToRevolut']);
        Route::post('/callback', [RevolutController::class, 'handleCallback']);
        Route::get('/connections', [RevolutController::class, 'listConnections']);
        Route::get('/connections/{connection}/accounts', [RevolutController::class, 'getAccounts'])->middleware('throttle:30,1');
        Route::post('/connections/{connection}/sync', [RevolutController::class, 'syncTransactions'])->middleware('throttle:10,1');
        Route::post('/connections/{connection}/pay', [RevolutController::class, 'sendPayment'])->middleware('throttle:30,1');
        Route::post('/connections/{connection}/bulk-pay', [RevolutController::class, 'sendBulkPayment'])->middleware('throttle:10,1');
        Route::delete('/connections/{connection}', [RevolutController::class, 'removeConnection']);
    });

    // QuickBooks Online API Routes
    Route::prefix('qbo')->middleware('throttle:60,1')->group(function (): void {
        Route::get('/connect', [QboController::class, 'connect']);
        Route::get('/callback', [QboController::class, 'callback']);
        Route::get('/connections', [QboController::class, 'listConnections']);
        Route::post('/connections/{connection}/sync', [QboController::class, 'sync'])->middleware('throttle:10,1');
        Route::delete('/connections/{connection}', [QboController::class, 'removeConnection']);
    });

    // Wise API Routes
    Route::prefix('wise')->middleware('throttle:60,1')->group(function (): void {
        Route::get('/authorize', [WiseController::class, 'redirectToWise']);
        Route::post('/callback', [WiseController::class, 'handleCallback']);
        Route::get('/connections', [WiseController::class, 'listConnections']);
        Route::get('/connections/{connection}/accounts', [WiseController::class, 'getAccounts'])->middleware('throttle:30,1');
        Route::post('/connections/{connection}/sync', [WiseController::class, 'syncTransactions'])->middleware('throttle:10,1');
        Route::delete('/connections/{connection}', [WiseController::class, 'removeConnection']);
    });
});

// Plaid OAuth redirect endpoint (public endpoint, no auth required)
Route::get('/plaid/oauth-redirect', [PlaidController::class, 'handleOAuthRedirect']);

// Plaid Webhook (public endpoint, no auth required)
Route::post('/webhooks/plaid', [PlaidWebhookController::class, 'handle']);

// Revolut Webhook (public endpoint, no auth required)
Route::post('/webhooks/revolut', [RevolutWebhookController::class, 'handle']);

// Wise Webhook (public endpoint, no auth required)
Route::post('/webhooks/wise', [WiseWebhookController::class, 'handle']);

// QBO Webhook (public endpoint, HMAC-verified — no Sanctum auth)
Route::post('/webhooks/qbo', [QboWebhookController::class, 'handle']);
