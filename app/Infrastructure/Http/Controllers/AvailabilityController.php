<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers;

use App\Application\Availability\UseCases\ListAvailableSlotsUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

final class AvailabilityController
{
    public function __construct(
        private readonly ListAvailableSlotsUseCase $useCase,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'service_id' => ['required', 'integer', 'min:1'],
            'date' => ['required', 'date_format:Y-m-d'],
        ]);

        try {
            $slots = $this->useCase->execute((int) $validated['service_id'], (string) $validated['date']);
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json(['data' => $slots]);
    }
}
