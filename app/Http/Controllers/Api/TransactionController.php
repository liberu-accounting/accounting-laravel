<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class TransactionController extends Controller
{
    /**
     * Acting user's current team, or -1 when there is none — a sentinel that
     * matches no row (team ids are positive), so a tenantless caller gets an
     * empty result / 403 rather than leaking unassigned (team_id IS NULL) rows.
     */
    private function currentTeamId(): int
    {
        return (int) (auth()->user()->current_team_id ?? -1);
    }

    public function index(): AnonymousResourceCollection
    {
        return TransactionResource::collection(
            Transaction::where('team_id', $this->currentTeamId())->paginate(15)
        );
    }

    public function show(Transaction $transaction): TransactionResource
    {
        abort_unless($transaction->team_id === $this->currentTeamId(), 403);

        return new TransactionResource($transaction);
    }

    public function store(Request $request): TransactionResource
    {
        $validated = $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'amount' => 'required|numeric',
            'transaction_date' => 'required|date',
            'description' => 'required|string',
            'currency_id' => 'sometimes|nullable|exists:currencies,currency_id',
            'exchange_rate' => 'sometimes|nullable|numeric',
        ]);

        // user_id/team_id are not fillable — set them explicitly (property assignment
        // bypasses mass-assignment) rather than relying on the model's creating hook.
        $transaction = new Transaction($validated);
        $transaction->user_id = $request->user()->id;
        $transaction->team_id = $request->user()->current_team_id;
        $transaction->save();

        return new TransactionResource($transaction);
    }

    public function update(Request $request, Transaction $transaction): TransactionResource
    {
        abort_unless($transaction->team_id === $this->currentTeamId(), 403);

        $validated = $request->validate([
            'account_id' => 'sometimes|exists:accounts,id',
            'amount' => 'sometimes|numeric',
            'transaction_date' => 'sometimes|date',
            'description' => 'sometimes|string',
            'currency_id' => 'sometimes|nullable|exists:currencies,currency_id',
            'exchange_rate' => 'sometimes|nullable|numeric',
        ]);

        $transaction->update($validated);

        return new TransactionResource($transaction);
    }

    public function destroy(Transaction $transaction): Response
    {
        abort_unless($transaction->team_id === $this->currentTeamId(), 403);

        $transaction->delete();

        return response()->noContent();
    }
}
