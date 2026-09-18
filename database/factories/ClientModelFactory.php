<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Infrastructure\Persistence\Eloquent\Models\ClientModel;
use Illuminate\Database\Eloquent\Factories\Factory;

final class ClientModelFactory extends Factory
{
    protected $model = ClientModel::class;

    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'dni' => (string) fake()->unique()->numberBetween(10000000, 99999999),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->unique()->safeEmail(),
        ];
    }
}
