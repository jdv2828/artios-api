<?php

declare(strict_types=1);

namespace Tests\Feature\Service;

use App\Infrastructure\Persistence\Eloquent\Models\ServiceModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PublicActiveServicesTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_only_active_services_publicly(): void
    {
        ServiceModel::query()->create([
            'name' => 'Activo',
            'duration_minutes' => 30,
            'price' => 1000,
            'active' => true,
        ]);

        ServiceModel::query()->create([
            'name' => 'Inactivo',
            'duration_minutes' => 30,
            'price' => 1000,
            'active' => false,
        ]);

        $this->getJson('/api/services/active')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Activo');
    }
}
