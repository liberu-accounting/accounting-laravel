<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\XeroConnection;
use App\Services\XeroService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class XeroController extends Controller
{
    public function __construct(private readonly XeroService $xero) {}

    public function connect(): JsonResponse
    {
        return response()->json([
            'authorization_url' => $this->xero->getAuthorizationUrl(Str::random(40)),
        ]);
    }

    public function callback(Request $request): JsonResponse
    {
        $validated = $request->validate(['code' => ['required', 'string']]);

        $connection = $this->xero->handleCallback((int) $request->user()->id, $validated['code']);

        return response()->json(['success' => true, 'tenant_id' => $connection->tenant_id]);
    }

    public function sync(Request $request, XeroConnection $connection): JsonResponse
    {
        abort_unless($connection->user_id === $request->user()->id, 403);

        return response()->json(['success' => true, 'invoices_synced' => $this->xero->pullInvoices($connection)]);
    }
}
