<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\InventoryCostLayer;
use Illuminate\Support\Facades\DB;

class InventoryValuationService
{
    public function calculateCostOfGoodsSold(InventoryTransaction $transaction): float|int|null
    {
        $item = $transaction->inventoryItem;
        
        return match ($item->valuation_method) {
            'fifo' => $this->calculateFIFOCost($transaction),
            'lifo' => $this->calculateLIFOCost($transaction),
            'average' => $this->calculateAverageCost($transaction),
            default => null,
        };
    }

    private function calculateFIFOCost(InventoryTransaction $transaction): float|int
    {
        $remainingQty = $transaction->quantity;
        $totalCost = 0;

        $costLayers = InventoryCostLayer::where('inventory_item_id', $transaction->inventory_item_id)
            ->where('quantity', '>', 0)
            ->orderBy('purchase_date')
            ->get();

        foreach ($costLayers as $layer) {
            $qtyFromLayer = min($remainingQty, $layer->quantity);
            $totalCost += $qtyFromLayer * $layer->unit_cost;
            $layer->quantity -= $qtyFromLayer;
            $layer->save();
            
            $remainingQty -= $qtyFromLayer;
            if ($remainingQty <= 0) {
                break;
            }
        }

        return $totalCost;
    }

    private function calculateLIFOCost(InventoryTransaction $transaction): float|int
    {
        $remainingQty = $transaction->quantity;
        $totalCost = 0;

        $costLayers = InventoryCostLayer::where('inventory_item_id', $transaction->inventory_item_id)
            ->where('quantity', '>', 0)
            ->orderBy('purchase_date', 'desc')
            ->get();

        foreach ($costLayers as $layer) {
            $qtyFromLayer = min($remainingQty, $layer->quantity);
            $totalCost += $qtyFromLayer * $layer->unit_cost;
            $layer->quantity -= $qtyFromLayer;
            $layer->save();
            
            $remainingQty -= $qtyFromLayer;
            if ($remainingQty <= 0) {
                break;
            }
        }

        return $totalCost;
    }

    private function calculateAverageCost(InventoryTransaction $transaction): int|float
    {
        $item = $transaction->inventoryItem;
        return $transaction->quantity * $item->average_cost;
    }

    public function updateAverageCost(InventoryItem $item, $purchaseQty, $purchaseCost): void
    {
        $totalValue = ($item->current_quantity * $item->average_cost) + 
                     ($purchaseQty * $purchaseCost);
        $totalQty = $item->current_quantity + $purchaseQty;
        
        $item->average_cost = $totalValue / $totalQty;
        $item->save();
    }

    public function getInventoryValuation(InventoryItem $item)
    {
        switch ($item->valuation_method) {
            case 'fifo':
            case 'lifo':
                return InventoryCostLayer::where('inventory_item_id', $item->id)
                    ->where('quantity', '>', 0)
                    ->sum(DB::raw('quantity * unit_cost'));
            case 'average':
                return $item->current_quantity * $item->average_cost;
        }
    }
}