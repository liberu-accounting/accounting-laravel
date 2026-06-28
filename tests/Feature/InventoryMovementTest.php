<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Account;
use App\Models\InventoryCostLayer;
use App\Models\InventoryItem;
use App\Models\User;
use App\Services\InventoryMovementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryMovementTest extends TestCase
{
    use RefreshDatabase;

    private function service(): InventoryMovementService
    {
        return app(InventoryMovementService::class);
    }

    public function test_record_purchase_adds_cost_layer_and_stock(): void
    {
        $item = InventoryItem::factory()->create(['valuation_method' => 'fifo', 'current_quantity' => 0, 'average_cost' => 0]);

        $this->service()->recordPurchase($item, 10, 5.00);

        $item->refresh();
        $this->assertSame(10, $item->current_quantity);
        $this->assertEquals(5.00, $item->average_cost);
        $this->assertDatabaseHas('inventory_cost_layers', [
            'inventory_item_id' => $item->id,
            'quantity' => 10,
            'unit_cost' => 5.00,
        ]);
    }

    public function test_second_purchase_updates_weighted_average(): void
    {
        $item = InventoryItem::factory()->create(['valuation_method' => 'average', 'current_quantity' => 0, 'average_cost' => 0]);

        $this->service()->recordPurchase($item, 10, 4.00);  // avg 4
        $this->service()->recordPurchase($item, 10, 6.00);  // (40 + 60) / 20 = 5

        $this->assertEquals(5.00, $item->fresh()->average_cost);
        $this->assertSame(20, $item->fresh()->current_quantity);
    }

    public function test_record_sale_posts_cogs_and_reduces_stock(): void
    {
        $this->actingAs(User::factory()->create());

        $inventoryAsset = Account::factory()->create(['account_type' => 'asset', 'normal_balance' => 'debit']);
        $cogsExpense = Account::factory()->create(['account_type' => 'expense', 'normal_balance' => 'debit']);
        $item = InventoryItem::factory()->create([
            'valuation_method' => 'fifo',
            'current_quantity' => 20,
            'account_id' => $inventoryAsset->id,
        ]);
        InventoryCostLayer::create(['inventory_item_id' => $item->id, 'quantity' => 10, 'unit_cost' => 2, 'purchase_date' => '2026-01-01']);
        InventoryCostLayer::create(['inventory_item_id' => $item->id, 'quantity' => 10, 'unit_cost' => 3, 'purchase_date' => '2026-02-01']);

        $result = $this->service()->recordSale($item, 15, $cogsExpense);  // FIFO: 10*2 + 5*3 = 35

        $this->assertEquals(35.00, $result['cogs']);
        $this->assertSame(5, $item->fresh()->current_quantity);
        $this->assertTrue($result['journal_entry']->isBalanced());
        $this->assertEquals(35.00, $result['journal_entry']->total_debits);
    }
}
