<?php

declare(strict_types=1);

namespace Tests\Feature\Availability;

use App\Infrastructure\Persistence\Eloquent\Models\AppointmentModel;
use App\Infrastructure\Persistence\Eloquent\Models\ClientModel;
use App\Infrastructure\Persistence\Eloquent\Models\EmployeeBlockedDateModel;
use App\Infrastructure\Persistence\Eloquent\Models\EmployeeBreakModel;
use App\Infrastructure\Persistence\Eloquent\Models\EmployeeScheduleModel;
use App\Infrastructure\Persistence\Eloquent\Models\ServiceModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AvailabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_slots_with_breaks_and_bookings_marked_unavailable(): void
    {
        $service = ServiceModel::query()->create([
            'name' => 'Servicio',
            'duration_minutes' => 60,
            'price' => 1000,
            'active' => true,
        ]);

        $employee = User::factory()->create();
        $employee->services()->attach($service->id);

        // Monday schedule 09:00-12:00 with a break 10:00-11:00
        EmployeeScheduleModel::query()->create([
            'user_id' => $employee->id,
            'day_of_week' => 1,
            'start_time' => '09:00',
            'end_time' => '12:00',
        ]);

        EmployeeBreakModel::query()->create([
            'user_id' => $employee->id,
            'day_of_week' => 1,
            'start_time' => '10:00',
            'end_time' => '11:00',
        ]);

        $client = ClientModel::query()->create([
            'first_name' => 'Ana',
            'last_name' => 'Perez',
            'dni' => '30123456',
            'phone' => '+54 11 5555-8888',
            'email' => 'ana@example.com',
        ]);

        $monday = now()->next('Monday')->format('Y-m-d');

        // Existing booking at 09:00
        AppointmentModel::query()->create([
            'client_id' => $client->id,
            'employee_id' => $employee->id,
            'service_id' => $service->id,
            'scheduled_at' => $monday.' 09:00:00',
            'status' => 'scheduled',
            'confirmation_token' => 'token-x',
            'remind_1_day_before' => false,
            'remind_30_mins_before' => false,
            'remind_1_hour_before' => true,
        ]);

        $response = $this->getJson('/api/availability?service_id='.$service->id.'&date='.$monday);
        $response->assertOk();

        $data = $response->json('data');

        // 09:00 exists but is booked
        self::assertContains(['time' => '09:00', 'available' => false], $data);
        // 10:00 exists but falls in break
        self::assertContains(['time' => '10:00', 'available' => false], $data);
        // 11:00 is available
        self::assertContains(['time' => '11:00', 'available' => true], $data);
    }

    public function test_it_marks_slots_unavailable_when_date_is_blocked(): void
    {
        $service = ServiceModel::query()->create([
            'name' => 'Servicio',
            'duration_minutes' => 30,
            'price' => 1000,
            'active' => true,
        ]);

        $employee = User::factory()->create();
        $employee->services()->attach($service->id);

        EmployeeScheduleModel::query()->create([
            'user_id' => $employee->id,
            'day_of_week' => 1,
            'start_time' => '09:00',
            'end_time' => '10:00',
        ]);

        $monday = now()->next('Monday')->format('Y-m-d');

        EmployeeBlockedDateModel::query()->create([
            'user_id' => $employee->id,
            'date' => $monday,
            'reason' => 'Vacaciones',
        ]);

        $response = $this->getJson('/api/availability?service_id='.$service->id.'&date='.$monday);
        $response->assertOk();

        $data = $response->json('data');
        self::assertNotEmpty($data);
        foreach ($data as $slot) {
            self::assertFalse($slot['available']);
        }
    }
}
