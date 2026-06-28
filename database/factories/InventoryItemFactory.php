<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Account;
use App\Models\InventoryItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryItem>
 */
class InventoryItemFactory extends Factory
{
    protected $model = InventoryItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true),
            'sku' => 'SKU-'.$this->faker->unique()->numberBetween(1000, 999999),
            'description' => $this->faker->sentence(),
            'unit_price' => $this->faker->randomFloat(2, 1, 500),
            'current_quantity' => 0,
            'reorder_point' => 5,
            'account_id' => Account::factory(),
            'valuation_method' => 'fifo',
            'average_cost' => 0,
            'is_active' => true,
        ];
    }
}
