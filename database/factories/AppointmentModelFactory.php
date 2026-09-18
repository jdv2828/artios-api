<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Appointment\AppointmentStatus;
use App\Infrastructure\Persistence\Eloquent\Models\AppointmentModel;
use App\Infrastructure\Persistence\Eloquent\Models\ClientModel;
use App\Infrastructure\Persistence\Eloquent\Models\ServiceModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

final class AppointmentModelFactory extends Factory
{
    protected $model = AppointmentModel::class;

    public function definition(): array
    {
        $employeeId = User::query()
            ->where('role', 'employee')
            ->inRandomOrder()
            ->value('id');

        return [
            'client_id' => ClientModel::factory(),
            'employee_id' => $employeeId !== null ? (int) $employeeId : null,
            'service_id' => ServiceModel::factory(),
            'scheduled_at' => fake()->dateTimeBetween('-3 days', '+2 weeks'),
            'status' => fake()->randomElement([
                AppointmentStatus::Scheduled->value,
                AppointmentStatus::Confirmed->value,
                AppointmentStatus::Completed->value,
                AppointmentStatus::Cancelled->value,
                AppointmentStatus::NoShow->value,
            ]),
            'confirmation_token' => (string) Str::uuid(),
            'remind_1_day_before' => fake()->boolean(),
            'remind_30_mins_before' => fake()->boolean(),
            'remind_1_hour_before' => true,
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn (): array => [
            'status' => AppointmentStatus::Confirmed->value,
        ]);
    }
}
