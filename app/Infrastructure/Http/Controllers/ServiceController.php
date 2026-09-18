<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers;

use App\Application\Service\Exceptions\ServiceNotFoundException;
use App\Application\Service\UseCases\CreateServiceUseCase;
use App\Application\Service\UseCases\DeleteServiceUseCase;
use App\Application\Service\UseCases\GetServiceUseCase;
use App\Application\Service\UseCases\ListServicesUseCase;
use App\Application\Service\UseCases\UpdateServiceUseCase;
use App\Infrastructure\Http\Requests\Service\StoreServiceRequest;
use App\Infrastructure\Http\Requests\Service\UpdateServiceRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ServiceController
{
    public function __construct(
        private readonly ListServicesUseCase $listServicesUseCase,
        private readonly GetServiceUseCase $getServiceUseCase,
        private readonly CreateServiceUseCase $createServiceUseCase,
        private readonly UpdateServiceUseCase $updateServiceUseCase,
        private readonly DeleteServiceUseCase $deleteServiceUseCase,
    ) {
    }

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => array_map($this->normalize(...), $this->listServicesUseCase->execute()),
        ]);
    }

    public function store(StoreServiceRequest $request): JsonResponse
    {
        $service = $this->createServiceUseCase->execute($request->toDto());

        return response()->json([
            'data' => $this->normalize($service),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        try {
            $service = $this->getServiceUseCase->execute($id);
        } catch (ServiceNotFoundException) {
            return response()->json(['message' => 'Service not found.'], 404);
        }

        return response()->json(['data' => $this->normalize($service)]);
    }

    public function update(UpdateServiceRequest $request, int $id): JsonResponse
    {
        try {
            $service = $this->updateServiceUseCase->execute($request->toDto($id));
        } catch (ServiceNotFoundException) {
            return response()->json(['message' => 'Service not found.'], 404);
        }

        return response()->json(['data' => $this->normalize($service)]);
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->deleteServiceUseCase->execute($id);
        } catch (ServiceNotFoundException) {
            return response()->json(['message' => 'Service not found.'], 404);
        }

        return response()->json([], 204);
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
