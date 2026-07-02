<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    protected $model = Project::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = $this->faker->dateTimeBetween('-6 months', 'now');
        $end = $this->faker->dateTimeBetween($start, '+6 months');

        return [
            'name' => $this->faker->unique()->company().' Project',
            'code' => strtoupper($this->faker->unique()->bothify('PRJ-####')),
            'description' => $this->faker->optional()->sentence(),
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
            'status' => $this->faker->randomElement(['active', 'on_hold', 'completed']),
            'allocation_percentage' => $this->faker->randomFloat(2, 10, 100),
        ];
    }
}
