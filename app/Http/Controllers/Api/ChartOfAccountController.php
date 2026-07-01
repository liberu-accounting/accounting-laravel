<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;

class ChartOfAccountController extends Controller
{
    private const TYPES = 'asset,liability,equity,revenue,income,expense';

    public function index(Request $request): LengthAwarePaginator
    {
        return Account::where('user_id', $request->user()->id)
            ->orderBy('account_number')
            ->paginate(25);
    }

    public function store(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        // Scope account references/uniqueness to the acting user (accounts.user_id):
        // parent_id can't point at another user's account (IDOR), and account_number
        // is unique per-tenant rather than global.
        $validated = $request->validate([
            'account_number' => ['required', 'integer', Rule::unique('accounts', 'account_number')->where('user_id', $userId)],
            'account_name' => 'required|string',
            'account_type' => 'required|in:'.self::TYPES,
            'normal_balance' => 'sometimes|in:debit,credit',
            'opening_balance' => 'sometimes|numeric',
            'description' => 'sometimes|nullable|string',
            'parent_id' => ['sometimes', 'nullable', Rule::exists('accounts', 'id')->where('user_id', $userId)],
            'is_active' => 'sometimes|boolean',
        ]);

        $account = Account::create($validated);

        return response()->json($account, 201);
    }

    public function show(Request $request, Account $chartOfAccount): JsonResponse
    {
        $this->authorizeOwner($request, $chartOfAccount);

        return response()->json($chartOfAccount);
    }

    public function update(Request $request, Account $chartOfAccount): JsonResponse
    {
        $this->authorizeOwner($request, $chartOfAccount);

        $userId = $request->user()->id;

        $validated = $request->validate([
            'account_number' => ['sometimes', 'integer', Rule::unique('accounts', 'account_number')->where('user_id', $userId)->ignore($chartOfAccount->id)],
            'account_name' => 'sometimes|string',
            'account_type' => 'sometimes|in:'.self::TYPES,
            'normal_balance' => 'sometimes|in:debit,credit',
            'opening_balance' => 'sometimes|numeric',
            'description' => 'sometimes|nullable|string',
            'parent_id' => ['sometimes', 'nullable', Rule::exists('accounts', 'id')->where('user_id', $userId)],
            'is_active' => 'sometimes|boolean',
        ]);

        $chartOfAccount->update($validated);

        return response()->json($chartOfAccount);
    }

    public function destroy(Request $request, Account $chartOfAccount): JsonResponse
    {
        $this->authorizeOwner($request, $chartOfAccount);

        $chartOfAccount->delete();

        return response()->json(['deleted' => true]);
    }

    private function authorizeOwner(Request $request, Account $account): void
    {
        abort_unless($account->user_id === $request->user()->id, 403);
    }
}
