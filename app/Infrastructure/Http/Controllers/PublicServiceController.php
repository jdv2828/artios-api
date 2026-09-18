<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers;

use App\Application\Service\UseCases\ListActiveServicesUseCase;
use Illuminate\Http\JsonResponse;

final class PublicServiceController
{
    public function __construct(
        private readonly ListActiveServicesUseCase $useCase,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        return response()->json([
            'data' => array_map($this->normalize(...), $this->useCase->execute()),
        ]);
    }

    private function normalize(object $service): array
    {
        return [
            'id' => $service->id(),
            'name' => $service->name(),
            'duration_minutes' => $service->durationMinutes(),
            'price' => $service->price(),
            'active' => $service->active(),
        ];
    }
}
