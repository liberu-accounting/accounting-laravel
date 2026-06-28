<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Account;
use App\Models\InventoryCostLayer;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\User;
use App\Services\InventoryPostingService;
use App\Services\InventoryValuationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryValuationTest extends TestCase
{
    use RefreshDatabase;

    private function valuation(): InventoryValuationService
    {
        return app(InventoryValuationService::class);
    }

    private function layeredItem(string $method): InventoryItem
    {
        $item = InventoryItem::factory()->create(['valuation_method' => $method]);

        InventoryCostLayer::create(['inventory_item_id' => $item->id, 'quantity' => 10, 'unit_cost' => 2, 'purchase_date' => '2026-01-01']);
        InventoryCostLayer::create(['inventory_item_id' => $item->id, 'quantity' => 10, 'unit_cost' => 3, 'purchase_date' => '2026-02-01']);

        return $item;
    }

    public function test_fifo_cost_of_goods_sold(): void
    {
        $item = $this->layeredItem('fifo');
        $sale = new InventoryTransaction(['inventory_item_id' => $item->id, 'quantity' => 15]);

        // 10 @ 2 (oldest) + 5 @ 3 = 35
        $this->assertEquals(35.0, $this->valuation()->calculateCostOfGoodsSold($sale));
    }

    public function test_lifo_cost_of_goods_sold(): void
    {
        $item = $this->layeredItem('lifo');
        $sale = new InventoryTransaction(['inventory_item_id' => $item->id, 'quantity' => 15]);

        // 10 @ 3 (newest) + 5 @ 2 = 40
        $this->assertEquals(40.0, $this->valuation()->calculateCostOfGoodsSold($sale));
    }

    public function test_average_inventory_valuation(): void
    {
        $item = InventoryItem::factory()->create([
            'valuation_method' => 'average',
            'average_cost' => 4,
            'current_quantity' => 10,
        ]);

        $this->assertEquals(40.0, $this->valuation()->getInventoryValuation($item->fresh()));
    }

    public function test_cogs_posts_balanced_journal_entry(): void
    {
        $this->actingAs(User::factory()->create());

        $inventoryAsset = Account::factory()->create(['account_type' => 'asset', 'normal_balance' => 'debit']);
        $cogsExpense = Account::factory()->create(['account_type' => 'expense', 'normal_balance' => 'debit']);
        $item = InventoryItem::factory()->create(['account_id' => $inventoryAsset->id]);

        $entry = app(InventoryPostingService::class)->postCogs($item, 35.00, $cogsExpense);

        $this->assertTrue($entry->isBalanced());
        $this->assertEquals(35.0, $entry->total_debits);
        $this->assertEquals(35.0, $entry->total_credits);
        $this->assertSame(2, $entry->lines()->count());
    }
}
