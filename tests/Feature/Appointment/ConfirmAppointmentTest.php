<?php

declare(strict_types=1);

namespace Tests\Feature\Appointment;

use App\Infrastructure\Persistence\Eloquent\Models\AppointmentModel;
use App\Infrastructure\Persistence\Eloquent\Models\ServiceModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\CreatesEmployeeAvailability;
use Tests\TestCase;

final class ConfirmAppointmentTest extends TestCase
{
    use RefreshDatabase;
    use CreatesEmployeeAvailability;

    public function test_it_confirms_an_appointment_by_token(): void
    {
        $service = $this->createService();

        $this->postJson('/api/appointments', [
            'first_name' => 'Ana',
            'last_name' => 'Perez',
            'dni' => '30123456',
            'phone' => '+54 11 5555-8888',
            'email' => 'ana@example.com',
            'service_id' => $service->id,
            'scheduled_at' => now()->addDay()->setTime(10, 0)->format('Y-m-d H:i:s'),
            'remind_1_day_before' => true,
            'remind_30_mins_before' => false,
        ])->assertCreated();

        $appointment = AppointmentModel::query()->firstOrFail();

        $this->getJson('/api/appointments/confirm/'.$appointment->confirmation_token)
            ->assertOk()
            ->assertJsonPath('data.status', 'confirmed');

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'confirmed',
        ]);
    }

    public function test_it_returns_404_for_an_unknown_token(): void
    {
        $this->getJson('/api/appointments/confirm/unknown-token')->assertNotFound();
    }

    private function createService(): ServiceModel
    {
        $service = ServiceModel::query()->create([
            'name' => 'Consulta general',
            'duration_minutes' => 30,
            'price' => 1000,
            'active' => true,
        ]);

        $this->createEmployeeWithServiceAndFullDaySchedule($service);

        return $service;
    }
}
