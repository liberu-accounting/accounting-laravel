<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\InventoryCostLayer;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\Transaction;

class InventoryService
{
    public function __construct(protected InventoryValuationService $valuationService) {}

    /**
     * Acting user's current team, or -1 (matches no row — team ids are positive)
     * so a tenantless caller gets an empty result instead of leaking every
     * team's items via team_id IS NULL. Mirrors GeneralLedgerService.
     */
    private function scopedTeamId(): int
    {
        return auth()->user()?->current_team_id ?? -1;
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
            'transaction_type' => $type,
        ]);

        if ($type === 'purchase') {
            InventoryCostLayer::create([
                'inventory_item_id' => $item->id,
                'quantity' => $quantity,
                'unit_cost' => $unitPrice,
                'purchase_date' => now(),
            ]);
            if ($item->valuation_method === 'average') {
                $this->valuationService->updateAverageCost($item, $quantity, $unitPrice);
            }
        } elseif ($type === 'sale') {
            $costOfGoodsSold = $this->valuationService->calculateCostOfGoodsSold($inventoryTx);
            $inventoryTx->update(['cost_of_goods_sold' => $costOfGoodsSold]);
        }

        $change = $type === 'sale' ? -$quantity : $quantity;
        $item->updateQuantity($change);

        return $inventoryTx;
    }

    public function checkLowStock()
    {
        return InventoryItem::where('team_id', $this->scopedTeamId())
            ->where('is_active', true)
            ->where('current_quantity', '<=', 'reorder_point')
            ->get();
    }

    public function getInventoryValue()
    {
        return InventoryItem::where('team_id', $this->scopedTeamId())
            ->where('is_active', true)
            ->get()
            ->sum(fn (InventoryItem $item) => $this->valuationService->getInventoryValuation($item));
    }

    public function getInventoryReport()
    {
        return InventoryItem::where('team_id', $this->scopedTeamId())
            ->where('is_active', true)
            ->get()
            ->map(fn (InventoryItem $item): array => [
                'id' => $item->id,
                'name' => $item->name,
                'sku' => $item->sku,
                'quantity' => $item->current_quantity,
                'valuation_method' => $item->valuation_method,
                'total_value' => $this->valuationService->getInventoryValuation($item),
            ]);
    }
}
