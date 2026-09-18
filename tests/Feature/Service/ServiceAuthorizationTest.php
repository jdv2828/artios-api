<?php

declare(strict_types=1);

namespace Tests\Feature\Service;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ServiceAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_cannot_create_update_or_delete_services(): void
    {
        $employee = User::factory()->create();
        $this->actingAs($employee, 'sanctum');

        $this->postJson('/api/services', [
            'name' => 'Nuevo',
            'duration_minutes' => 30,
            'price' => 1000,
            'active' => true,
        ])->assertForbidden();
    }
}
