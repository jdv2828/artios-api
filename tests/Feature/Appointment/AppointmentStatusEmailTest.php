<?php

declare(strict_types=1);

namespace Tests\Feature\Appointment;

use App\Infrastructure\Mail\AppointmentCancelledMail;
use App\Infrastructure\Mail\AppointmentNotificationMail;
use App\Infrastructure\Persistence\Eloquent\Models\AppointmentModel;
use App\Infrastructure\Persistence\Eloquent\Models\ClientModel;
use App\Infrastructure\Persistence\Eloquent\Models\ServiceModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\CreatesEmployeeAvailability;
use Tests\TestCase;

final class AppointmentStatusEmailTest extends TestCase
{
    use RefreshDatabase;
    use CreatesEmployeeAvailability;

    public function test_confirming_an_appointment_sends_confirmation_email_to_the_client(): void
    {
        Mail::fake();

        $admin = User::factory()->admin()->create();
        $service = $this->createService();
        $employee = $this->createEmployeeWithServiceAndFullDaySchedule($service);
        $client = ClientModel::factory()->create();

        $appointment = AppointmentModel::factory()->create([
            'client_id' => $client->id,
            'employee_id' => $employee->id,
            'service_id' => $service->id,
            'scheduled_at' => now()->addDay()->setTime(9, 0),
            'status' => 'scheduled',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->patchJson('/api/appointments/'.$appointment->id, ['status' => 'confirmed'])
            ->assertOk()
            ->assertJsonPath('data.status', 'confirmed');

        Mail::assertSent(
            AppointmentNotificationMail::class,
            fn (AppointmentNotificationMail $mail): bool => $mail->hasTo($client->email)
        );
    }

    public function test_cancelling_an_appointment_sends_cancelled_email_to_the_client(): void
    {
        Mail::fake();

        $admin = User::factory()->admin()->create();
        $service = $this->createService();
        $employee = $this->createEmployeeWithServiceAndFullDaySchedule($service);
        $client = ClientModel::factory()->create();

        $appointment = AppointmentModel::factory()->create([
            'client_id' => $client->id,
            'employee_id' => $employee->id,
            'service_id' => $service->id,
            'scheduled_at' => now()->addDay()->setTime(9, 0),
            'status' => 'confirmed',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->patchJson('/api/appointments/'.$appointment->id, ['status' => 'cancelled'])
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');

        Mail::assertSent(
            AppointmentCancelledMail::class,
            fn (AppointmentCancelledMail $mail): bool => $mail->hasTo($client->email)
        );
    }

    public function test_completing_an_appointment_does_not_send_any_email(): void
    {
        Mail::fake();

        $admin = User::factory()->admin()->create();
        $service = $this->createService();
        $employee = $this->createEmployeeWithServiceAndFullDaySchedule($service);
        $client = ClientModel::factory()->create();

        $appointment = AppointmentModel::factory()->create([
            'client_id' => $client->id,
            'employee_id' => $employee->id,
            'service_id' => $service->id,
            'scheduled_at' => now()->addDay()->setTime(9, 0),
            'status' => 'confirmed',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->patchJson('/api/appointments/'.$appointment->id, ['status' => 'completed'])
            ->assertOk()
            ->assertJsonPath('data.status', 'completed');

        Mail::assertNothingSent();
    }

    public function test_marking_no_show_does_not_send_any_email(): void
    {
        Mail::fake();

        $admin = User::factory()->admin()->create();
        $service = $this->createService();
        $employee = $this->createEmployeeWithServiceAndFullDaySchedule($service);
        $client = ClientModel::factory()->create();

        $appointment = AppointmentModel::factory()->create([
            'client_id' => $client->id,
            'employee_id' => $employee->id,
            'service_id' => $service->id,
            'scheduled_at' => now()->addDay()->setTime(9, 0),
            'status' => 'confirmed',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->patchJson('/api/appointments/'.$appointment->id, ['status' => 'no_show'])
            ->assertOk()
            ->assertJsonPath('data.status', 'no_show');

        Mail::assertNothingSent();
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
}
