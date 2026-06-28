<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class QboWebhookController extends Controller
{
    /**
     * Receive QBO change notifications. Verified with the HMAC-SHA256 verifier
     * token Intuit signs each payload with (header `intuit-signature`).
     */
    public function handle(Request $request): JsonResponse
    {
        $rawBody = $request->getContent();

        if (! $this->verifySignature($rawBody, (string) $request->header('intuit-signature'))) {
            Log::warning('QBO webhook signature verification failed');

            return response()->json(['success' => false], 401);
        }

        // ponytail: enqueue a pull for the affected realm here once async sync lands.
        // The verified notification body lists changed entities per realmId.
        Log::info('QBO webhook received', ['events' => $request->input('eventNotifications', [])]);

        return response()->json(['success' => true]);
    }

    private function verifySignature(string $rawBody, string $signature): bool
    {
        $token = config('services.qbo.webhook_verifier_token');

        if (empty($token) || $signature === '') {
            return false;
        }

        $expected = base64_encode(hash_hmac('sha256', $rawBody, (string) $token, true));

        return hash_equals($expected, $signature);
    }
}
