<?php

namespace Database\Seeders;

use App\Domain\User\UserRole;
use App\Models\User;
use App\Infrastructure\Persistence\Eloquent\Models\AppointmentModel;
use App\Infrastructure\Persistence\Eloquent\Models\ClientModel;
use App\Infrastructure\Persistence\Eloquent\Models\EmployeeBreakModel;
use App\Infrastructure\Persistence\Eloquent\Models\EmployeeScheduleModel;
use App\Infrastructure\Persistence\Eloquent\Models\ServiceModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@turnero.com'],
            [
                'name' => 'Admin',
                'password' => 'secret',
                'role' => UserRole::Admin,
                'email_verified_at' => now(),
            ],
        );

        $services = collect([
            ['name' => 'Corte de cabello', 'duration_minutes' => 30, 'price' => 1200, 'active' => true],
            ['name' => 'Tintura', 'duration_minutes' => 90, 'price' => 3500, 'active' => true],
            ['name' => 'Barba', 'duration_minutes' => 20, 'price' => 900, 'active' => true],
            ['name' => 'Manicura', 'duration_minutes' => 45, 'price' => 1800, 'active' => true],
            ['name' => 'Pedicura', 'duration_minutes' => 50, 'price' => 2000, 'active' => true],
        ])->map(fn (array $service): ServiceModel => ServiceModel::query()->create($service));

        // Seed a few professionals with default schedules and service knowledge.
        $employees = User::factory()->count(3)->create();
        foreach ($employees as $employee) {
            // Give every professional all services so availability works out of the box.
            $employee->services()->attach($services->pluck('id')->all());

            // Mon-Fri 09-18 with a lunch break 12-13, Sat 09-13.
            for ($day = 1; $day <= 5; $day++) {
                EmployeeScheduleModel::query()->create([
                    'user_id' => $employee->id,
                    'day_of_week' => $day,
                    'start_time' => '09:00',
                    'end_time' => '18:00',
                ]);
                EmployeeBreakModel::query()->create([
                    'user_id' => $employee->id,
                    'day_of_week' => $day,
                    'start_time' => '12:00',
                    'end_time' => '13:00',
                ]);
            }
            EmployeeScheduleModel::query()->create([
                'user_id' => $employee->id,
                'day_of_week' => 6,
                'start_time' => '09:00',
                'end_time' => '13:00',
            ]);
        }

        $clients = ClientModel::factory()->count(20)->create();

        $today = Carbon::today();

        // Ensure there are appointments for today so the dashboard isn't empty.
        AppointmentModel::factory()
            ->count(10)
            ->state(function () use ($clients, $services, $employees, $today): array {
                $employee = $employees->random();
                return [
                    'client_id' => $clients->random()->id,
                    'employee_id' => $employee->id,
                    'service_id' => $services->random()->id,
                    'scheduled_at' => $today->copy()->addHours(fake()->numberBetween(9, 18))->addMinutes(fake()->randomElement([0, 30])),
                ];
            })
            ->create();

        // Additional realistic mix across past/future days.
        AppointmentModel::factory()
            ->count(40)
            ->state(function () use ($clients, $services, $employees): array {
                $employee = $employees->random();
                return [
                    'client_id' => $clients->random()->id,
                    'employee_id' => $employee->id,
                    'service_id' => $services->random()->id,
                ];
            })
            ->create();
    }
}
