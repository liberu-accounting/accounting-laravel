<?php

namespace Database\Factories;

use App\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BankStatement>
 */
class BankStatementFactory extends Factory
{
    public function definition(): array
    {
        $credits = $this->faker->randomFloat(2, 0, 5000);
        $debits = $this->faker->randomFloat(2, 0, 5000);
        return [
            'account_id' => Account::factory(),
            'statement_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'total_credits' => $credits,
            'total_debits' => $debits,
            'ending_balance' => $credits - $debits,
            'reconciled' => false,
        ];
    }
}
