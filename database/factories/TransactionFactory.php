<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'transaction_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'transaction_description' => $this->faker->sentence(),
            'amount' => $this->faker->randomFloat(2, -1000, 1000),
            'type' => $this->faker->randomElement(['credit', 'debit']),
            'status' => 'posted',
            'reconciled' => false,
        ];
    }
}
