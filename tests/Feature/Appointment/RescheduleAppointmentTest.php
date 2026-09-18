<?php

declare(strict_types=1);

namespace Tests\Feature\Appointment;

use App\Infrastructure\Persistence\Eloquent\Models\AppointmentModel;
use App\Infrastructure\Persistence\Eloquent\Models\ClientModel;
use App\Infrastructure\Persistence\Eloquent\Models\ServiceModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Tests\CreatesEmployeeAvailability;
use Tests\TestCase;

final class RescheduleAppointmentTest extends TestCase
{
    use RefreshDatabase;
    use CreatesEmployeeAvailability;

    public function test_admin_can_reschedule_an_appointment(): void
    {
        Mail::fake();
        Bus::fake();

        $admin = User::factory()->admin()->create();
        $service = $this->createService();
        $employee = $this->createEmployeeWithServiceAndFullDaySchedule($service);
        $client = ClientModel::factory()->create();

        $appointment = AppointmentModel::factory()->create([
            'client_id' => $client->id,
            'employee_id' => $employee->id,
            'service_id' => $service->id,
            'scheduled_at' => now()->addDays(2)->setTime(9, 0),
            'status' => 'scheduled',
        ]);

        $newScheduledAt = now()->addDays(5)->setTime(9, 0)->format('Y-m-d H:i:s');

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/appointments/'.$appointment->id.'/reschedule', [
                'scheduled_at' => $newScheduledAt,
                'employee_id' => $employee->id,
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'scheduled')
            ->assertJsonPath('data.employee_id', $employee->id);

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'employee_id' => $employee->id,
            'status' => 'scheduled',
        ]);
    }

    public function test_it_returns_409_for_occupied_target_slot(): void
    {
        Mail::fake();
        Bus::fake();

        $admin = User::factory()->admin()->create();
        $service = $this->createService();
        $employee = $this->createEmployeeWithServiceAndFullDaySchedule($service);
        $clientA = ClientModel::factory()->create();
        $clientB = ClientModel::factory()->create();

        $targetSlot = now()->addDays(3)->setTime(9, 0);

        $appointmentA = AppointmentModel::factory()->create([
            'client_id' => $clientA->id,
            'employee_id' => $employee->id,
            'service_id' => $service->id,
            'scheduled_at' => now()->addDays(2)->setTime(9, 0),
            'status' => 'scheduled',
        ]);

        AppointmentModel::factory()->create([
            'client_id' => $clientB->id,
            'employee_id' => $employee->id,
            'service_id' => $service->id,
            'scheduled_at' => $targetSlot,
            'status' => 'scheduled',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/appointments/'.$appointmentA->id.'/reschedule', [
                'scheduled_at' => $targetSlot->format('Y-m-d H:i:s'),
                'employee_id' => $employee->id,
            ])
            ->assertStatus(409)
            ->assertJsonPath('message', 'The selected slot at '.$targetSlot->format('Y-m-d H:i').' is not available.');
    }

    public function test_it_returns_404_for_missing_appointment(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/appointments/999/reschedule', [
                'scheduled_at' => now()->addDays(5)->format('Y-m-d H:i:s'),
                'employee_id' => 1,
            ])
            ->assertNotFound();
    }

    public function test_it_returns_422_for_invalid_params(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/appointments/1/reschedule', [])
            ->assertUnprocessable();
    }

    public function test_employee_role_cannot_reschedule(): void
    {
        $employee = User::factory()->create();
        $service = $this->createService();
        $client = ClientModel::factory()->create();

        $appointment = AppointmentModel::factory()->create([
            'client_id' => $client->id,
            'employee_id' => $employee->id,
            'service_id' => $service->id,
            'scheduled_at' => now()->addDays(2),
            'status' => 'scheduled',
        ]);

        $this->actingAs($employee, 'sanctum')
            ->postJson('/api/appointments/'.$appointment->id.'/reschedule', [
                'scheduled_at' => now()->addDays(5)->format('Y-m-d H:i:s'),
                'employee_id' => $employee->id,
            ])
            ->assertForbidden();
    }

    public function test_unauthenticated_cannot_reschedule(): void
    {
        $this->postJson('/api/appointments/1/reschedule', [
            'scheduled_at' => now()->addDays(5)->format('Y-m-d H:i:s'),
            'employee_id' => 1,
        ])->assertUnauthorized();
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
