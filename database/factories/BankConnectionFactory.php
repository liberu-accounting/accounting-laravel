<?php

namespace Database\Factories;

use App\Models\BankConnection;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BankConnectionFactory extends Factory
{
    protected $model = BankConnection::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'bank_id' => $this->faker->uuid(),
            'institution_name' => $this->faker->randomElement([
                'Chase Bank',
                'Bank of America',
                'Wells Fargo',
                'Citibank',
                'US Bank',
                'Capital One',
            ]),
            'plaid_access_token' => encrypt('access-sandbox-' . $this->faker->uuid()),
            'plaid_item_id' => 'item-' . $this->faker->uuid(),
            'plaid_institution_id' => 'ins_' . $this->faker->randomNumber(5),
            'plaid_cursor' => null,
            'status' => $this->faker->randomElement(['active', 'disconnected', 'error']),
            'last_synced_at' => $this->faker->optional()->dateTimeBetween('-30 days', 'now'),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    public function disconnected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'disconnected',
        ]);
    }

    public function withCursor(): static
    {
        return $this->state(fn (array $attributes) => [
            'plaid_cursor' => 'cursor-' . $this->faker->uuid(),
        ]);
    }
}
