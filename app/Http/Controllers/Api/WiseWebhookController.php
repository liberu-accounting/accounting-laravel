<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\WiseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;

class WiseWebhookController extends Controller
{
    protected WiseService $wiseService;

    public function __construct(WiseService $wiseService)
    {
        $this->wiseService = $wiseService;
    }

    /**
     * Handle incoming Wise webhooks
     */
    public function handle(Request $request): JsonResponse
    {
        try {
            $rawBody = $request->getContent();
            $signature = $request->header('X-Signature-SHA256', '');
            $publicKey = config('services.wise.webhook_public_key', '');

            if (!$this->wiseService->verifyWebhookSignature($rawBody, $signature, $publicKey)) {
                Log::warning('Wise webhook signature verification failed', [
                    'ip' => $request->ip(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid signature',
                ], 401);
            }

            $payload = $request->all();
            $event = $payload['event_type'] ?? null;

            Log::info('Wise webhook received', [
                'event' => $event,
            ]);

            match ($event) {
                'transfers#state-change' => $this->handleTransferStateChange($payload),
                'transfers#active-cases' => $this->handleTransferActiveCases($payload),
                'balances#credit' => $this->handleBalanceCredit($payload),
                'balances#update' => $this->handleBalanceUpdate($payload),
                default => Log::info('Unhandled Wise webhook event', ['event' => $event]),
            };

            return response()->json([
                'success' => true,
                'message' => 'Webhook processed',
            ]);
        } catch (Exception $e) {
            Log::error('Wise webhook processing failed', [
                'error' => $e->getMessage(),
            ]);

            // Return 200 to prevent Wise from retrying on processing errors
            return response()->json([
                'success' => false,
                'message' => 'Webhook processing failed',
            ], 200);
        }
    }

    /**
     * Handle transfers#state-change event
     */
    protected function handleTransferStateChange(array $payload): void
    {
        $data = $payload['data'] ?? [];

        Log::info('Wise transfer state changed', [
            'transfer_id' => $data['resource']['id'] ?? null,
            'new_state' => $data['current_state'] ?? null,
        ]);
    }

    /**
     * Handle transfers#active-cases event
     */
    protected function handleTransferActiveCases(array $payload): void
    {
        $data = $payload['data'] ?? [];

        Log::info('Wise transfer has active case', [
            'transfer_id' => $data['resource']['id'] ?? null,
        ]);
    }

    /**
     * Handle balances#credit event
     */
    protected function handleBalanceCredit(array $payload): void
    {
        $data = $payload['data'] ?? [];

        Log::info('Wise balance credited', [
            'balance_id' => $data['resource']['id'] ?? null,
            'currency' => $data['currency'] ?? null,
        ]);
    }

    /**
     * Handle balances#update event
     */
    protected function handleBalanceUpdate(array $payload): void
    {
        $data = $payload['data'] ?? [];

        Log::info('Wise balance updated', [
            'balance_id' => $data['resource']['id'] ?? null,
        ]);
    }
}
