

<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\Transaction;
use App\Models\InventoryTransaction;
use App\Models\InventoryCostLayer;

class InventoryService
{
    protected $valuationService;

    public function __construct(InventoryValuationService $valuationService)
    {
        $this->valuationService = $valuationService;
    }

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

        if ($type === 'purchase') {
            InventoryCostLayer::create([
                'inventory_item_id' => $item->id,
                'quantity' => $quantity,
                'unit_cost' => $unitPrice,
                'purchase_date' => now()
            ]);

            if ($item->valuation_method === 'average') {
                $this->valuationService->updateAverageCost($item, $quantity, $unitPrice);
            }
        } else if ($type === 'sale') {
            $costOfGoodsSold = $this->valuationService->calculateCostOfGoodsSold($inventoryTx);
            $inventoryTx->update(['cost_of_goods_sold' => $costOfGoodsSold]);
        }

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
            ->get()
            ->sum(function ($item) {
                return $this->valuationService->getInventoryValuation($item);
            });
    }

    public function getInventoryReport()
    {
        return InventoryItem::where('is_active', true)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'sku' => $item->sku,
                    'quantity' => $item->current_quantity,
                    'valuation_method' => $item->valuation_method,
                    'total_value' => $this->valuationService->getInventoryValuation($item)
                ];
            });
    }
}