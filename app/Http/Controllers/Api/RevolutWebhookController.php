<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BankConnection;
use App\Services\RevolutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;

class RevolutWebhookController extends Controller
{
    protected RevolutService $revolutService;

    public function __construct(RevolutService $revolutService)
    {
        $this->revolutService = $revolutService;
    }

    /**
     * Handle incoming Revolut Business webhooks
     */
    public function handle(Request $request): JsonResponse
    {
        try {
            $rawBody = $request->getContent();
            $signature = $request->header('Revolut-Signature', '');

            if (!$this->revolutService->verifyWebhookSignature($rawBody, $signature)) {
                Log::warning('Revolut webhook signature verification failed', [
                    'ip' => $request->ip(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid signature',
                ], 401);
            }

            $payload = $request->all();
            $event = $payload['event'] ?? null;

            Log::info('Revolut webhook received', [
                'event' => $event,
            ]);

            match ($event) {
                'TransactionCreated' => $this->handleTransactionCreated($payload),
                'TransactionStateChanged' => $this->handleTransactionStateChanged($payload),
                default => Log::info('Unhandled Revolut webhook event', ['event' => $event]),
            };

            return response()->json([
                'success' => true,
                'message' => 'Webhook processed',
            ]);
        } catch (Exception $e) {
            Log::error('Revolut webhook processing failed', [
                'error' => $e->getMessage(),
            ]);

            // Return 200 to prevent Revolut from retrying on processing errors
            return response()->json([
                'success' => false,
                'message' => 'Webhook processing failed',
            ], 200);
        }
    }

    /**
     * Handle TransactionCreated event
     */
    protected function handleTransactionCreated(array $payload): void
    {
        $transactionData = $payload['data'] ?? [];

        Log::info('Revolut transaction created', [
            'transaction_id' => $transactionData['id'] ?? null,
        ]);
    }

    /**
     * Handle TransactionStateChanged event
     */
    protected function handleTransactionStateChanged(array $payload): void
    {
        $transactionData = $payload['data'] ?? [];

        Log::info('Revolut transaction state changed', [
            'transaction_id' => $transactionData['id'] ?? null,
            'new_state' => $transactionData['state'] ?? null,
        ]);
    }
}
