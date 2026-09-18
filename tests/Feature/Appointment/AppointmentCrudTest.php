<?php

declare(strict_types=1);

namespace Tests\Feature\Appointment;

use App\Infrastructure\Persistence\Eloquent\Models\ServiceModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\CreatesEmployeeAvailability;
use Tests\TestCase;

final class AppointmentCrudTest extends TestCase
{
    use RefreshDatabase;
    use CreatesEmployeeAvailability;

    public function test_it_lists_and_filters_appointments(): void
    {
        $user = User::factory()->admin()->create([
            'email' => 'admin@turnero.com',
            'name' => 'Admin',
            'password' => 'secret',
        ]);

        $service = $this->createService();
        $this->createScheduledAppointment($service->id, '30123456', now()->addDay()->setTime(10, 0)->format('Y-m-d H:i:s'));
        $this->createScheduledAppointment($service->id, '40123456', now()->addDay()->setTime(10, 30)->format('Y-m-d H:i:s'));

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/appointments?client_dni=30123456')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.client_dni', '30123456')
            ->assertJsonPath('data.0.client_name', 'Ana Perez');
    }

    public function test_it_filters_by_client_dni_prefix(): void
    {
        $user = User::factory()->admin()->create([
            'email' => 'admin@turnero.com',
            'name' => 'Admin',
            'password' => 'secret',
        ]);

        $service = $this->createService();
        $this->createScheduledAppointment($service->id, '30123456', now()->addDay()->setTime(10, 0)->format('Y-m-d H:i:s'));
        $this->createScheduledAppointment($service->id, '30198765', now()->addDay()->setTime(10, 30)->format('Y-m-d H:i:s'));

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/appointments?client_dni=301')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 2);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/appointments?client_dni=999')
            ->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.total', 0);
    }

    public function test_it_filters_by_client_name_prefix(): void
    {
        $user = User::factory()->admin()->create([
            'email' => 'admin@turnero.com',
            'name' => 'Admin',
            'password' => 'secret',
        ]);

        $service = $this->createService();
        $this->createScheduledAppointment($service->id, '30123456', now()->addDay()->setTime(11, 0)->format('Y-m-d H:i:s'), 'Ana', 'Perez');
        $this->createScheduledAppointment($service->id, '30198765', now()->addDay()->setTime(11, 30)->format('Y-m-d H:i:s'), 'Ana', 'Garcia');

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/appointments?client_name=Ana')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 2);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/appointments?client_name=Perez')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.total', 1);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/appointments?client_name=Zzzz')
            ->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.total', 0);
    }

    public function test_it_shows_an_appointment(): void
    {
        $user = User::factory()->admin()->create([
            'email' => 'admin@turnero.com',
            'name' => 'Admin',
            'password' => 'secret',
        ]);

        $service = $this->createService();
        $response = $this->createScheduledAppointment($service->id, '30123456');

        $appointmentId = (int) $response->json('id');

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/appointments/'.$appointmentId)
            ->assertOk()
            ->assertJsonPath('data.id', $appointmentId);
    }

    public function test_it_changes_appointment_status(): void
    {
        $user = User::factory()->admin()->create([
            'email' => 'admin@turnero.com',
            'name' => 'Admin',
            'password' => 'secret',
        ]);

        $service = $this->createService();
        $response = $this->createScheduledAppointment($service->id, '30123456');
        $appointmentId = (int) $response->json('id');

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/appointments/'.$appointmentId, ['status' => 'confirmed'])
            ->assertOk()
            ->assertJsonPath('data.status', 'confirmed');

        $this->assertDatabaseHas('appointments', [
            'id' => $appointmentId,
            'status' => 'confirmed',
        ]);
    }

    public function test_it_rejects_invalid_status_transition(): void
    {
        $user = User::factory()->admin()->create([
            'email' => 'admin@turnero.com',
            'name' => 'Admin',
            'password' => 'secret',
        ]);

        $service = $this->createService();
        $response = $this->createScheduledAppointment($service->id, '30123456');
        $appointmentId = (int) $response->json('id');

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/appointments/'.$appointmentId, ['status' => 'completed'])
            ->assertUnprocessable();
    }

    public function test_it_filters_by_date_range(): void
    {
        $user = User::factory()->admin()->create([
            'email' => 'admin@turnero.com',
            'name' => 'Admin',
            'password' => 'secret',
        ]);

        $service = $this->createService();
        $this->createScheduledAppointment($service->id, '30123456', now()->addDays(1)->setTime(10, 0)->format('Y-m-d H:i:s'));
        $this->createScheduledAppointment($service->id, '30198765', now()->addDays(5)->setTime(10, 0)->format('Y-m-d H:i:s'));
        $this->createScheduledAppointment($service->id, '30234567', now()->addDays(10)->setTime(10, 0)->format('Y-m-d H:i:s'));

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/appointments?scheduled_date_from=' . now()->addDays(2)->format('Y-m-d') . '&scheduled_date_to=' . now()->addDays(8)->format('Y-m-d'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.total', 1);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/appointments?scheduled_date_from=' . now()->addDays(1)->format('Y-m-d') . '&scheduled_date_to=' . now()->addDays(10)->format('Y-m-d'))
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.total', 3);
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

    private function createScheduledAppointment(
        int $serviceId,
        string $dni,
        ?string $scheduledAt = null,
        string $firstName = 'Ana',
        string $lastName = 'Perez',
    ) {
        return $this->postJson('/api/appointments', [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'dni' => $dni,
            'phone' => '+54 11 5555-8888',
            'email' => 'ana@example.com',
            'service_id' => $serviceId,
            'scheduled_at' => $scheduledAt ?? now()->addDay()->setTime(10, 0)->format('Y-m-d H:i:s'),
            'remind_1_day_before' => true,
            'remind_30_mins_before' => false,
        ]);
    }
}
