<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SageConnection;
use App\Services\SageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SageController extends Controller
{
    public function __construct(private readonly SageService $sage) {}

    public function connect(): JsonResponse
    {
        return response()->json([
            'authorization_url' => $this->sage->getAuthorizationUrl(Str::random(40)),
        ]);
    }

    public function callback(Request $request): JsonResponse
    {
        $validated = $request->validate(['code' => ['required', 'string']]);

        $connection = $this->sage->handleCallback((int) $request->user()->id, $validated['code']);

        // Connections are team-shared: stamp the acting team (no creating hook does it).
        $connection->team_id = $request->user()->current_team_id;
        $connection->save();

        return response()->json(['success' => true, 'business_id' => $connection->business_id]);
    }

    public function sync(Request $request, SageConnection $connection): JsonResponse
    {
        abort_unless($connection->team_id === ($request->user()->current_team_id ?? -1), 403);

        return response()->json(['success' => true, 'invoices_synced' => $this->sage->pullInvoices($connection)]);
    }
}
