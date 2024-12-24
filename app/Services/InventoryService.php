

<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\Transaction;
use App\Models\InventoryTransaction;

class InventoryService
{
    public function createInventoryTransaction(
        Transaction $transaction,
        InventoryItem $item,
        int $quantity,
        float $unitPrice,
        string $type
    ) {
        $inventoryTx = InventoryTransaction::create([
            'inventory_item_id' => $item->id,
            'transaction_id' => $transaction->id,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'transaction_type' => $type
        ]);

        $change = $type === 'sale' ? -$quantity : $quantity;
        $item->updateQuantity($change);

        return $inventoryTx;
    }

    public function checkLowStock()
    {
        return InventoryItem::where('is_active', true)
            ->where('current_quantity', '<=', 'reorder_point')
            ->get();
    }

    public function getInventoryValue()
    {
        return InventoryItem::where('is_active', true)
            ->sum(\DB::raw('unit_price * current_quantity'));
    }
}