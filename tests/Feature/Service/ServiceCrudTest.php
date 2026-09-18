<?php

declare(strict_types=1);

namespace Tests\Feature\Service;

use App\Models\User;
use App\Infrastructure\Persistence\Eloquent\Models\ServiceModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ServiceCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_lists_updates_and_deletes_services(): void
    {
        $user = User::factory()->admin()->create([
            'email' => 'admin@turnero.com',
            'name' => 'Admin',
            'password' => 'secret',
        ]);

        $this->actingAs($user, 'sanctum');

        $createResponse = $this->postJson('/api/services', [
            'name' => 'Corte',
            'duration_minutes' => 30,
            'price' => 1200,
        ]);

        $createResponse->assertCreated();
        $createResponse->assertJsonPath('data.name', 'Corte');

        $serviceId = (int) $createResponse->json('data.id');

        $this->getJson('/api/services')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/services/'.$serviceId)->assertOk()->assertJsonPath('data.name', 'Corte');

        $this->putJson('/api/services/'.$serviceId, [
            'name' => 'Corte premium',
            'duration_minutes' => 45,
            'price' => 1800,
            'active' => false,
        ])->assertOk()->assertJsonPath('data.active', false);

        $this->deleteJson('/api/services/'.$serviceId)->assertNoContent();
        $this->assertDatabaseMissing('services', ['id' => $serviceId]);
    }

    public function test_it_returns_validation_errors_for_bad_payload(): void
    {
        $user = User::factory()->admin()->create();
        $this->actingAs($user, 'sanctum');

        $this->postJson('/api/services', [])->assertUnprocessable();
    }

    public function test_it_returns_404_when_service_does_not_exist(): void
    {
        $user = User::factory()->admin()->create();
        $this->actingAs($user, 'sanctum');

        $this->getJson('/api/services/999')->assertNotFound();
        $this->putJson('/api/services/999', [
            'name' => 'X',
            'duration_minutes' => 10,
            'price' => 100,
            'active' => true,
        ])->assertNotFound();
        $this->deleteJson('/api/services/999')->assertNotFound();
    }
}
