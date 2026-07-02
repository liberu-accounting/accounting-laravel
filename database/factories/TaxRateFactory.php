<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\TaxRate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TaxRate>
 */
class TaxRateFactory extends Factory
{
    protected $model = TaxRate::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->word(),
            'rate' => $this->faker->randomFloat(2, 1, 25),
            'is_active' => true,
            'is_compound' => false,
            'team_id' => null,
        ];
    }
}
