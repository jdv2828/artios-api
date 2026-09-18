<?php

declare(strict_types=1);

namespace Tests;

use App\Infrastructure\Persistence\Eloquent\Models\EmployeeScheduleModel;
use App\Infrastructure\Persistence\Eloquent\Models\ServiceModel;
use App\Models\User;

trait CreatesEmployeeAvailability
{
    protected function createEmployeeWithServiceAndFullDaySchedule(ServiceModel $service): User
    {
        $employee = User::factory()->create();
        $employee->services()->attach($service->id);

        for ($day = 0; $day <= 6; $day++) {
            EmployeeScheduleModel::query()->create([
                'user_id' => $employee->id,
                'day_of_week' => $day,
                'start_time' => '00:00',
                'end_time' => '23:59',
            ]);
        }

        return $employee;
    }
}
