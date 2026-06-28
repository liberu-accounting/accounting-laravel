<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class BillController extends Controller
{
    public function index(Request $request): LengthAwarePaginator
    {
        return Bill::where('team_id', $request->user()->current_team_id)
            ->latest()
            ->paginate(25);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'vendor_id' => 'required|exists:vendors,vendor_id',
            'bill_date' => 'required|date',
            'due_date' => 'required|date',
            'subtotal_amount' => 'sometimes|numeric',
            'tax_amount' => 'sometimes|numeric',
            'total_amount' => 'required|numeric',
            'reference_number' => 'sometimes|nullable|string',
            'notes' => 'sometimes|nullable|string',
        ]);

        $validated['team_id'] = $request->user()->current_team_id;

        $bill = Bill::create($validated);

        return response()->json($bill, 201);
    }

    public function show(Request $request, Bill $bill): JsonResponse
    {
        $this->authorizeTeam($request, $bill);

        return response()->json($bill->load('items'));
    }

    public function update(Request $request, Bill $bill): JsonResponse
    {
        $this->authorizeTeam($request, $bill);

        $validated = $request->validate([
            'vendor_id' => 'sometimes|exists:vendors,vendor_id',
            'bill_date' => 'sometimes|date',
            'due_date' => 'sometimes|date',
            'total_amount' => 'sometimes|numeric',
            'status' => 'sometimes|string',
            'payment_status' => 'sometimes|string',
            'notes' => 'sometimes|nullable|string',
        ]);

        $bill->update($validated);

        return response()->json($bill);
    }

    public function destroy(Request $request, Bill $bill): JsonResponse
    {
        $this->authorizeTeam($request, $bill);

        $bill->delete();

        return response()->json(['deleted' => true]);
    }

    private function authorizeTeam(Request $request, Bill $bill): void
    {
        abort_unless((int) $bill->team_id === (int) $request->user()->current_team_id, 403);
    }
}
