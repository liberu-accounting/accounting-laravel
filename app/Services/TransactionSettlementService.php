<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\Transaction;

/**
 * Settles a foreign-currency transaction at a (possibly changed) rate and posts
 * the resulting FX gain/loss to the ledger via FxRevaluationService.
 */
class TransactionSettlementService
{
    public function __construct(private readonly FxRevaluationService $fx) {}

    /**
     * Post the FX gain/loss between the transaction's booked rate and the
     * settlement rate, on its foreign amount. Returns null when there is no
     * difference (or the transaction was already in the reporting currency).
     */
    public function settle(Transaction $transaction, float $settledRate, Account $counter, Account $fxGain, Account $fxLoss): ?JournalEntry
    {
        $bookedRate = (float) ($transaction->exchange_rate ?? 1.0);
        $foreignAmount = (float) $transaction->amount;

        return $this->fx->postSettlement($foreignAmount, $bookedRate, $settledRate, $counter, $fxGain, $fxLoss);
    }
}
