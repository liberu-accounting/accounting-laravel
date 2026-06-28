<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Account;
use App\Models\InventoryItem;
use App\Models\JournalEntry;

/**
 * Posts the cost of goods sold for an inventory sale to the general ledger:
 * debit the COGS expense account, credit the item's inventory asset account.
 */
class InventoryPostingService
{
    public function postCogs(InventoryItem $item, float $cogs, Account $cogsExpense): JournalEntry
    {
        $entry = JournalEntry::create([
            'entry_date' => now(),
            'entry_type' => 'general',
            'memo' => 'COGS for '.$item->name,
        ]);

        $entry->lines()->create([
            'account_id' => $cogsExpense->id,
            'debit_amount' => $cogs,
            'credit_amount' => 0,
            'description' => 'Cost of goods sold',
        ]);

        $entry->lines()->create([
            'account_id' => $item->account_id,
            'debit_amount' => 0,
            'credit_amount' => $cogs,
            'description' => 'Inventory',
        ]);

        return $entry;
    }
}
