<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\GeneralLedgerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Read-only general-ledger reporting endpoints.
 */
class GeneralLedgerController extends Controller
{
    public function __construct(private readonly GeneralLedgerService $ledger) {}

    public function trialBalance(Request $request): JsonResponse
    {
        $date = $request->date('date') ?? now();

        return response()->json($this->ledger->getTrialBalance($date)->values());
    }

    public function balances(Request $request): JsonResponse
    {
        $start = $request->date('start_date') ?? now()->startOfYear();
        $end = $request->date('end_date') ?? now();

        return response()->json($this->ledger->getAccountBalances($start, $end)->values());
    }
}
