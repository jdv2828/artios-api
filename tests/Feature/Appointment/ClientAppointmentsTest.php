<?php

declare(strict_types=1);

namespace Tests\Feature\Appointment;

use App\Infrastructure\Persistence\Eloquent\Models\ClientModel;
use App\Infrastructure\Persistence\Eloquent\Models\ServiceModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\CreatesEmployeeAvailability;
use Tests\TestCase;

final class ClientAppointmentsTest extends TestCase
{
    use RefreshDatabase;
    use CreatesEmployeeAvailability;

    public function test_it_returns_a_clients_appointments_by_dni(): void
    {
        $service = $this->createService();
        ClientModel::query()->create([
            'first_name' => 'Ana',
            'last_name' => 'Perez',
            'dni' => '30123456',
            'phone' => '+54 11 5555-8888',
            'email' => 'ana@example.com',
        ]);

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

        $this->getJson('/api/clients/30123456/appointments')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_it_returns_404_when_client_does_not_exist(): void
    {
        $this->getJson('/api/clients/99999999/appointments')->assertNotFound();
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
