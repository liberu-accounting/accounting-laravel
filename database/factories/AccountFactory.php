<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Account>
 */
class AccountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        static $accountNumber = 1000;
        return [
            'user_id' => User::factory(),
            'account_number' => $accountNumber++,
            'account_name' => $this->faker->unique()->word() . ' ' . $this->faker->randomElement(['Account', 'Fund', 'Ledger']),
            'account_type' => $this->faker->randomElement(['asset', 'liability', 'equity', 'revenue', 'expense']),
            'normal_balance' => $this->faker->randomElement(['debit', 'credit']),
            'balance' => $this->faker->randomFloat(2, 0, 10000),
            'opening_balance' => 0,
            'is_active' => true,
            'allow_manual_entry' => true,
        ];
    }
}
