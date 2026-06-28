<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Account;
use App\Models\Invoice;
use App\Models\JournalEntry;

/**
 * Posts a customer invoice to the general ledger as a balanced journal entry:
 * debit Accounts Receivable for the total, credit each line item's revenue account.
 *
 * ponytail: tax handling adds a credit line to a tax-liability account when
 * invoice tax is non-zero — wire that in when invoice-level tax lands (the
 * active invoices schema has no tax column yet).
 */
class InvoicePostingService
{
    public function post(Invoice $invoice, Account $receivable): JournalEntry
    {
        $entry = JournalEntry::create([
            'entry_date' => $invoice->invoice_date,
            'entry_type' => 'general',
            'reference_number' => $invoice->getKey(),
            'memo' => 'Invoice #'.$invoice->getKey(),
        ]);

        $total = 0.0;

        foreach ($invoice->items as $item) {
            $entry->lines()->create([
                'account_id' => $item->account_id,
                'debit_amount' => 0,
                'credit_amount' => $item->amount,
                'description' => $item->description,
            ]);

            $total += (float) $item->amount;
        }

        // Debit AR for the full total — balances the entry by construction.
        $entry->lines()->create([
            'account_id' => $receivable->id,
            'debit_amount' => $total,
            'credit_amount' => 0,
            'description' => 'Accounts Receivable',
        ]);

        return $entry;
    }
}
