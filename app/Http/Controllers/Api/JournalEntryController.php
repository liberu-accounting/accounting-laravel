<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JournalEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class JournalEntryController extends Controller
{
    public function index(Request $request): LengthAwarePaginator
    {
        return JournalEntry::where('user_id', $request->user()->id)
            ->with('lines')
            ->latest()
            ->paginate(25);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'entry_date' => 'required|date',
            'entry_type' => 'sometimes|in:general,adjusting,closing,reversing',
            'reference_number' => 'sometimes|nullable|string',
            'memo' => 'sometimes|nullable|string',
            'lines' => 'sometimes|array',
            'lines.*.account_id' => 'required|exists:accounts,id',
            'lines.*.debit_amount' => 'sometimes|numeric|min:0',
            'lines.*.credit_amount' => 'sometimes|numeric|min:0',
            'lines.*.description' => 'sometimes|nullable|string',
        ]);

        $lines = $validated['lines'] ?? [];
        unset($validated['lines']);

        // Reject an unbalanced set of lines: debits must equal credits.
        if ($lines !== []) {
            $debits = array_sum(array_column($lines, 'debit_amount'));
            $credits = array_sum(array_column($lines, 'credit_amount'));
            if (bccomp((string) $debits, (string) $credits, 2) !== 0) {
                throw ValidationException::withMessages([
                    'lines' => 'Journal entry lines must balance (total debits must equal total credits).',
                ]);
            }
        }

        $entry = JournalEntry::create($validated);

        foreach ($lines as $line) {
            $entry->lines()->create([
                'account_id' => $line['account_id'],
                'debit_amount' => $line['debit_amount'] ?? 0,
                'credit_amount' => $line['credit_amount'] ?? 0,
                'description' => $line['description'] ?? null,
            ]);
        }

        return response()->json($entry->load('lines'), 201);
    }

    public function show(Request $request, JournalEntry $journalEntry): JsonResponse
    {
        abort_unless($journalEntry->user_id === $request->user()->id, 403);

        return response()->json($journalEntry->load('lines'));
    }

    public function destroy(Request $request, JournalEntry $journalEntry): JsonResponse
    {
        abort_unless($journalEntry->user_id === $request->user()->id, 403);
        abort_if($journalEntry->is_posted, 422, 'Posted entries cannot be deleted; reverse them instead.');

        $journalEntry->delete();

        return response()->json(['deleted' => true]);
    }
}
