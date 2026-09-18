<?php

declare(strict_types=1);

namespace Tests\Feature\Employee;

use App\Infrastructure\Persistence\Eloquent\Models\EmployeeBlockedDateModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class EmployeeBlockedDatesAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_can_block_and_unblock_own_dates(): void
    {
        $employee = User::factory()->create();
        $this->actingAs($employee, 'sanctum');

        $this->postJson('/api/employees/'.$employee->id.'/blocked-dates', [
            'date' => '2026-05-04',
            'reason' => 'Vacaciones',
        ])->assertCreated();

        $this->assertTrue(
            EmployeeBlockedDateModel::query()
                ->where('user_id', $employee->id)
                ->whereDate('date', '2026-05-04')
                ->exists()
        );

        $this->deleteJson('/api/employees/'.$employee->id.'/blocked-dates/2026-05-04')
            ->assertNoContent();

        $this->assertFalse(
            EmployeeBlockedDateModel::query()
                ->where('user_id', $employee->id)
                ->whereDate('date', '2026-05-04')
                ->exists()
        );
    }

    public function test_employee_cannot_block_or_unblock_other_employee_dates(): void
    {
        $employee = User::factory()->create();
        $other = User::factory()->create();

        EmployeeBlockedDateModel::query()->create([
            'user_id' => $other->id,
            'date' => '2026-05-04',
            'reason' => null,
        ]);

        $this->actingAs($employee, 'sanctum');

        $this->postJson('/api/employees/'.$other->id.'/blocked-dates', [
            'date' => '2026-05-05',
            'reason' => null,
        ])->assertForbidden();

        $this->deleteJson('/api/employees/'.$other->id.'/blocked-dates/2026-05-04')
            ->assertForbidden();
    }
}
