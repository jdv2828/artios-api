<?php

declare(strict_types=1);

namespace Tests\Feature\Appointment;

use App\Infrastructure\Persistence\Eloquent\Models\AppointmentModel;
use App\Infrastructure\Persistence\Eloquent\Models\ClientModel;
use App\Infrastructure\Persistence\Eloquent\Models\ServiceModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AppointmentAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_see_all_appointments_and_filter_by_employee(): void
    {
        $admin = User::factory()->admin()->create();
        $employeeA = User::factory()->create();
        $employeeB = User::factory()->create();

        $service = ServiceModel::query()->create([
            'name' => 'Servicio',
            'duration_minutes' => 30,
            'price' => 1000,
            'active' => true,
        ]);

        $client = ClientModel::factory()->create();

        $a = AppointmentModel::factory()->create([
            'client_id' => $client->id,
            'employee_id' => $employeeA->id,
            'service_id' => $service->id,
        ]);
        $b = AppointmentModel::factory()->create([
            'client_id' => $client->id,
            'employee_id' => $employeeB->id,
            'service_id' => $service->id,
        ]);

        $this->actingAs($admin, 'sanctum');

        $this->getJson('/api/appointments?per_page=100')
            ->assertOk()
            ->assertJsonPath('meta.total', 2);

        $this->getJson('/api/appointments?employee_id='.$employeeA->id.'&per_page=100')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $a->id);
    }

    public function test_employee_only_sees_own_appointments_even_if_filtering_other_employee(): void
    {
        $employeeA = User::factory()->create();
        $employeeB = User::factory()->create();

        $service = ServiceModel::query()->create([
            'name' => 'Servicio',
            'duration_minutes' => 30,
            'price' => 1000,
            'active' => true,
        ]);

        $client = ClientModel::factory()->create();

        $own = AppointmentModel::factory()->create([
            'client_id' => $client->id,
            'employee_id' => $employeeA->id,
            'service_id' => $service->id,
        ]);
        AppointmentModel::factory()->create([
            'client_id' => $client->id,
            'employee_id' => $employeeB->id,
            'service_id' => $service->id,
        ]);

        $this->actingAs($employeeA, 'sanctum');

        $this->getJson('/api/appointments?per_page=100')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $own->id);

        // Attempting to filter for another employee should still be scoped to self.
        $this->getJson('/api/appointments?employee_id='.$employeeB->id.'&per_page=100')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $own->id);
    }
}
