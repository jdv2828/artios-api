<?php

declare(strict_types=1);

namespace Tests\Feature\Appointment;

use App\Infrastructure\Mail\AppointmentNotificationMail;
use App\Infrastructure\Persistence\Eloquent\Models\ClientModel;
use App\Infrastructure\Persistence\Eloquent\Models\ServiceModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\CreatesEmployeeAvailability;
use Tests\TestCase;

final class ScheduleAppointmentTest extends TestCase
{
    use RefreshDatabase;
    use CreatesEmployeeAvailability;

    public function test_it_schedules_an_appointment_for_a_new_client(): void
    {
        Mail::fake();

        $service = $this->createService();
        $this->createEmployeeWithServiceAndFullDaySchedule($service);

        $payload = $this->payload($service->id, '+54 11 5555-8888');

        $response = $this->postJson('/api/appointments', $payload);

        $response->assertCreated();
        $this->assertDatabaseHas('clients', [
            'dni' => '30123456',
            'phone' => '+54 11 5555-8888',
            'email' => 'ana@example.com',
        ]);
        $this->assertDatabaseHas('appointments', [
            'service_id' => $service->id,
            'status' => 'scheduled',
        ]);

        Mail::assertSent(AppointmentNotificationMail::class, function (AppointmentNotificationMail $mail): bool {
            return $mail->kind === 'confirmation'
                && str_contains($mail->confirmationUrl, '/confirmacion?token=');
        });
    }

    public function test_it_updates_an_existing_clients_phone_when_it_changes(): void
    {
        Mail::fake();

        $service = $this->createService();
        $this->createEmployeeWithServiceAndFullDaySchedule($service);
        ClientModel::query()->create([
            'first_name' => 'Ana',
            'last_name' => 'Perez',
            'dni' => '30123456',
            'phone' => '+54 11 5555-8888',
            'email' => 'ana@example.com',
        ]);

        $response = $this->postJson('/api/appointments', $this->payload($service->id, '+54 11 4444-9999'));

        $response->assertCreated();
        $this->assertDatabaseCount('clients', 1);
        $this->assertDatabaseHas('clients', [
            'dni' => '30123456',
            'phone' => '+54 11 4444-9999',
            'email' => 'ana@example.com',
        ]);
    }

    public function test_it_returns_validation_errors_for_invalid_payload(): void
    {
        $this->postJson('/api/appointments', [])->assertUnprocessable();
    }

    public function test_it_rejects_a_non_existing_service_id(): void
    {
        $response = $this->postJson('/api/appointments', $this->payload(999, '+54 11 5555-8888'));

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['service_id']);
    }

    private function createService(): ServiceModel
    {
        return ServiceModel::query()->create([
            'name' => 'Consulta general',
            'duration_minutes' => 30,
            'price' => 1000,
            'active' => true,
        ]);
    }

    private function payload(int $serviceId, string $phone): array
    {
        return [
            'first_name' => 'Ana',
            'last_name' => 'Perez',
            'dni' => '30123456',
            'phone' => $phone,
            'email' => 'ana@example.com',
            'service_id' => $serviceId,
            'scheduled_at' => now()->addDay()->setTime(10, 0)->format('Y-m-d H:i:s'),
            'remind_1_day_before' => true,
            'remind_30_mins_before' => false,
        ];
    }
}
