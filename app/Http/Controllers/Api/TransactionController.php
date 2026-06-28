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
    public function index(): AnonymousResourceCollection
    {
        return TransactionResource::collection(
            Transaction::where('user_id', auth()->id())->paginate(15)
        );
    }

    public function show(Transaction $transaction): TransactionResource
    {
        abort_unless($transaction->user_id === auth()->id(), 403);

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

        $validated['user_id'] = auth()->id();

        $transaction = Transaction::create($validated);

        return new TransactionResource($transaction);
    }

    public function update(Request $request, Transaction $transaction): TransactionResource
    {
        abort_unless($transaction->user_id === auth()->id(), 403);

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
        abort_unless($transaction->user_id === auth()->id(), 403);

        $transaction->delete();

        return response()->noContent();
    }
}
