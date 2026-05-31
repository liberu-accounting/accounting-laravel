<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Budget>
 */
class BudgetFactory extends Factory
{
    public function definition(): array
    {
        $start = $this->faker->dateTimeBetween('-6 months', 'now');
        $end   = $this->faker->dateTimeBetween($start, '+6 months');

        return [
            'account_id'      => Account::factory(),
            'start_date'      => $start->format('Y-m-d'),
            'end_date'        => $end->format('Y-m-d'),
            'planned_amount'  => $this->faker->randomFloat(2, 100, 50000),
            'forecast_amount' => $this->faker->optional()->randomFloat(2, 100, 50000),
            'description'     => $this->faker->optional()->sentence(),
            'is_approved'     => false,
        ];
    }
}
