<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class InvoiceController extends Controller
{
    public function index(Request $request): LengthAwarePaginator
    {
        return Invoice::where('team_id', $request->user()->current_team_id)
            ->latest()
            ->paginate(25);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'vendor_id' => 'sometimes|nullable|exists:vendors,vendor_id',
            'invoice_date' => 'required|date',
            'due_date' => 'sometimes|nullable|date',
            'total_amount' => 'required|numeric',
            'payment_status' => 'required|string',
            'notes' => 'sometimes|nullable|string',
        ]);

        $validated['team_id'] = $request->user()->current_team_id;

        $invoice = Invoice::create($validated);

        return response()->json($invoice, 201);
    }

    public function show(Request $request, Invoice $invoice): JsonResponse
    {
        $this->authorizeTeam($request, $invoice);

        return response()->json($invoice->load('items'));
    }

    public function update(Request $request, Invoice $invoice): JsonResponse
    {
        $this->authorizeTeam($request, $invoice);

        $validated = $request->validate([
            'customer_id' => 'sometimes|exists:customers,id',
            'invoice_date' => 'sometimes|date',
            'due_date' => 'sometimes|nullable|date',
            'total_amount' => 'sometimes|numeric',
            'payment_status' => 'sometimes|string',
            'notes' => 'sometimes|nullable|string',
        ]);

        $invoice->update($validated);

        return response()->json($invoice);
    }

    public function destroy(Request $request, Invoice $invoice): JsonResponse
    {
        $this->authorizeTeam($request, $invoice);

        $invoice->delete();

        return response()->json(['deleted' => true]);
    }

    private function authorizeTeam(Request $request, Invoice $invoice): void
    {
        abort_unless((int) $invoice->team_id === (int) $request->user()->current_team_id, 403);
    }
}
