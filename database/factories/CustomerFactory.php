<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_name' => $this->faker->firstName(),
            'customer_last_name' => $this->faker->lastName(),
            'customer_address' => $this->faker->streetAddress(),
            'customer_email' => $this->faker->unique()->safeEmail(),
            'customer_phone' => $this->faker->unique()->numerify('+1##########'),
            'customer_city' => $this->faker->city(),
        ];
    }
}
