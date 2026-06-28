<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\QboConnection;
use App\Services\QuickBooksService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class QboController extends Controller
{
    public function __construct(private readonly QuickBooksService $qbo) {}

    /**
     * Begin the OAuth 2.0 flow — return the Intuit authorization URL for the client to open.
     */
    public function connect(): JsonResponse
    {
        // ponytail: state is not persisted server-side; this endpoint is Sanctum-authenticated
        // so the CSRF surface is small. Persist + verify state if connect ever becomes public.
        $state = Str::random(40);

        return response()->json([
            'authorization_url' => $this->qbo->getAuthorizationUrl($state),
        ]);
    }

    /**
     * OAuth callback — exchange the code for tokens and store the connection.
     */
    public function callback(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string'],
            'realmId' => ['required', 'string'],
        ]);

        $connection = $this->qbo->handleCallback(
            (int) $request->user()->id,
            $validated['code'],
            $validated['realmId'],
        );

        return response()->json([
            'success' => true,
            'realm_id' => $connection->realm_id,
        ]);
    }

    /**
     * @return JsonResponse list of the authenticated user's QBO connections
     */
    public function listConnections(Request $request): JsonResponse
    {
        return response()->json([
            'connections' => QboConnection::where('user_id', $request->user()->id)
                ->get(['id', 'realm_id', 'status', 'last_synced_at']),
        ]);
    }

    /**
     * Pull invoices from QBO into the local ledger.
     */
    public function sync(Request $request, QboConnection $connection): JsonResponse
    {
        abort_unless($connection->user_id === $request->user()->id, 403);

        $count = $this->qbo->pullInvoices($connection);

        return response()->json(['success' => true, 'invoices_synced' => $count]);
    }

    public function removeConnection(Request $request, QboConnection $connection): JsonResponse
    {
        abort_unless($connection->user_id === $request->user()->id, 403);

        $connection->delete();

        return response()->json(['success' => true]);
    }
}
