<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Currency>
 */
class CurrencyFactory extends Factory
{
    public function definition(): array
    {
        static $codes = ['USD', 'EUR', 'GBP', 'JPY', 'CAD', 'AUD', 'CHF', 'SEK', 'NOK', 'DKK'];
        static $index = 0;

        return [
            'code' => $codes[$index++ % count($codes)].'_'.$this->faker->unique()->numerify('###'),
            'name' => $this->faker->country().' Dollar',
            'symbol' => $this->faker->randomElement(['$', '€', '£', '¥']),
            'is_default' => false,
        ];
    }

    public function asDefault(): static
    {
        return $this->state(['is_default' => true, 'code' => 'USD_'.random_int(1, 999), 'symbol' => '$']);
    }
}
