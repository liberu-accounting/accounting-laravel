<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Estimate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class EstimateController extends Controller
{
    public function index(Request $request): LengthAwarePaginator
    {
        return Estimate::where('team_id', $request->user()->current_team_id)
            ->latest()
            ->paginate(25);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'estimate_date' => 'required|date',
            'expiration_date' => 'sometimes|nullable|date',
            'subtotal_amount' => 'sometimes|numeric',
            'tax_amount' => 'sometimes|numeric',
            'total_amount' => 'required|numeric',
            'status' => 'sometimes|string',
            'notes' => 'sometimes|nullable|string',
            'terms' => 'sometimes|nullable|string',
        ]);

        $validated['team_id'] = $request->user()->current_team_id;

        $estimate = Estimate::create($validated);

        return response()->json($estimate, 201);
    }

    public function show(Request $request, Estimate $estimate): JsonResponse
    {
        $this->authorizeTeam($request, $estimate);

        return response()->json($estimate->load('items'));
    }

    public function update(Request $request, Estimate $estimate): JsonResponse
    {
        $this->authorizeTeam($request, $estimate);

        $validated = $request->validate([
            'customer_id' => 'sometimes|exists:customers,id',
            'estimate_date' => 'sometimes|date',
            'expiration_date' => 'sometimes|nullable|date',
            'total_amount' => 'sometimes|numeric',
            'status' => 'sometimes|string',
            'notes' => 'sometimes|nullable|string',
        ]);

        $estimate->update($validated);

        return response()->json($estimate);
    }

    public function destroy(Request $request, Estimate $estimate): JsonResponse
    {
        $this->authorizeTeam($request, $estimate);

        $estimate->delete();

        return response()->json(['deleted' => true]);
    }

    private function authorizeTeam(Request $request, Estimate $estimate): void
    {
        abort_unless((int) $estimate->team_id === (int) $request->user()->current_team_id, 403);
    }
}
