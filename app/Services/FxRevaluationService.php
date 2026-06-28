<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Account;
use App\Models\JournalEntry;

/**
 * Posts foreign-exchange gain/loss to the ledger when a foreign-currency
 * balance is settled at a rate different from the one it was booked at.
 */
class FxRevaluationService
{
    /**
     * Reporting-currency gain (+) or loss (−) on settling a foreign amount.
     */
    public function gainLoss(float $foreignAmount, float $bookedRate, float $settledRate): float
    {
        return round($foreignAmount * ($settledRate - $bookedRate), 2);
    }

    /**
     * Post a balanced FX gain/loss entry. Returns null when there is no difference.
     *
     * Gain → debit the counter account (more value realised), credit FX gain (income).
     * Loss → debit FX loss (expense), credit the counter account.
     */
    public function postSettlement(
        float $foreignAmount,
        float $bookedRate,
        float $settledRate,
        Account $counter,
        Account $fxGain,
        Account $fxLoss,
    ): ?JournalEntry {
        $diff = $this->gainLoss($foreignAmount, $bookedRate, $settledRate);

        if (abs($diff) < 0.005) {
            return null;
        }

        $amount = abs($diff);
        $entry = JournalEntry::create([
            'entry_date' => now(),
            'entry_type' => 'general',
            'memo' => 'FX revaluation on settlement',
        ]);

        if ($diff > 0) {
            $entry->lines()->create(['account_id' => $counter->id, 'debit_amount' => $amount, 'credit_amount' => 0, 'description' => 'FX gain on settlement']);
            $entry->lines()->create(['account_id' => $fxGain->id, 'debit_amount' => 0, 'credit_amount' => $amount, 'description' => 'Foreign exchange gain']);
        } else {
            $entry->lines()->create(['account_id' => $fxLoss->id, 'debit_amount' => $amount, 'credit_amount' => 0, 'description' => 'Foreign exchange loss']);
            $entry->lines()->create(['account_id' => $counter->id, 'debit_amount' => 0, 'credit_amount' => $amount, 'description' => 'FX loss on settlement']);
        }

        return $entry;
    }
}
