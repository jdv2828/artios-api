<?php

declare(strict_types=1);

namespace Tests\Feature\Employee;

use App\Infrastructure\Persistence\Eloquent\Models\ServiceModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class EmployeeAvailabilityCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_updates_and_reads_employee_availability(): void
    {
        $admin = User::factory()->admin()->create();
        $employee = User::factory()->create();

        $service = ServiceModel::query()->create([
            'name' => 'Servicio',
            'duration_minutes' => 30,
            'price' => 1000,
            'active' => true,
        ]);

        $this->actingAs($admin, 'sanctum');

        $this->putJson('/api/employees/'.$employee->id.'/availability', [
            'service_ids' => [$service->id],
            'schedules' => [
                ['day_of_week' => 1, 'start_time' => '09:00', 'end_time' => '18:00'],
            ],
            'breaks' => [
                ['day_of_week' => 1, 'start_time' => '12:00', 'end_time' => '13:00'],
            ],
        ])->assertOk();

        $this->getJson('/api/employees/'.$employee->id.'/availability')
            ->assertOk()
            ->assertJsonPath('data.user.id', $employee->id)
            ->assertJsonPath('data.service_ids.0', $service->id)
            ->assertJsonPath('data.schedules.0.day_of_week', 1)
            ->assertJsonPath('data.breaks.0.start_time', '12:00');
    }

    public function test_employee_can_read_and_update_own_availability(): void
    {
        $employee = User::factory()->create();

        $service = ServiceModel::query()->create([
            'name' => 'Servicio',
            'duration_minutes' => 30,
            'price' => 1000,
            'active' => true,
        ]);

        $this->actingAs($employee, 'sanctum');

        $this->putJson('/api/employees/'.$employee->id.'/availability', [
            'service_ids' => [$service->id],
            'schedules' => [
                ['day_of_week' => 1, 'start_time' => '09:00', 'end_time' => '18:00'],
            ],
            'breaks' => [],
        ])->assertOk();

        $this->getJson('/api/employees/'.$employee->id.'/availability')
            ->assertOk()
            ->assertJsonPath('data.user.id', $employee->id)
            ->assertJsonPath('data.service_ids.0', $service->id);
    }

    public function test_employee_cannot_read_other_employee_availability(): void
    {
        $employee = User::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($employee, 'sanctum');

        $this->getJson('/api/employees/'.$other->id.'/availability')
            ->assertForbidden();
    }

    public function test_employee_cannot_update_other_employee_availability(): void
    {
        $employee = User::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($employee, 'sanctum');

        $this->putJson('/api/employees/'.$other->id.'/availability', [
            'service_ids' => [],
            'schedules' => [],
            'breaks' => [],
        ])->assertForbidden();
    }
}
