<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Infrastructure\Persistence\Eloquent\Models\ServiceModel;
use Illuminate\Database\Eloquent\Factories\Factory;

final class ServiceModelFactory extends Factory
{
    protected $model = ServiceModel::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'duration_minutes' => fake()->numberBetween(15, 120),
            'price' => fake()->randomFloat(2, 500, 5000),
            'active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => [
            'active' => false,
        ]);
    }
}
