

<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\InventoryCostLayer;
use Illuminate\Support\Facades\DB;

class InventoryValuationService
{
    public function calculateCostOfGoodsSold(InventoryTransaction $transaction)
    {
        $item = $transaction->inventoryItem;
        
        switch ($item->valuation_method) {
            case 'fifo':
                return $this->calculateFIFOCost($transaction);
            case 'lifo':
                return $this->calculateLIFOCost($transaction);
            case 'average':
                return $this->calculateAverageCost($transaction);
        }
    }

    private function calculateFIFOCost(InventoryTransaction $transaction)
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
            if ($remainingQty <= 0) break;
        }

        return $totalCost;
    }

    private function calculateLIFOCost(InventoryTransaction $transaction)
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
            if ($remainingQty <= 0) break;
        }

        return $totalCost;
    }

    private function calculateAverageCost(InventoryTransaction $transaction)
    {
        $item = $transaction->inventoryItem;
        return $transaction->quantity * $item->average_cost;
    }

    public function updateAverageCost(InventoryItem $item, $purchaseQty, $purchaseCost)
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