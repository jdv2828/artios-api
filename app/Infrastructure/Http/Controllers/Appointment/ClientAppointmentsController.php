<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers\Appointment;

use App\Application\Appointment\UseCases\ListClientAppointmentsUseCase;
use App\Application\Client\Exceptions\ClientNotFoundException;
use Illuminate\Http\JsonResponse;

final class ClientAppointmentsController
{
    public function __construct(
        private readonly ListClientAppointmentsUseCase $useCase,
    ) {
    }

    public function __invoke(string $dni): JsonResponse
    {
        try {
            $appointments = $this->useCase->execute($dni);
        } catch (ClientNotFoundException) {
            return response()->json(['message' => 'Client not found.'], 404);
        }

        return response()->json([
            'data' => array_map($this->normalize(...), $appointments),
        ]);
    }

    private function normalize(object $appointment): array
    {
        return [
            'id' => $appointment->id(),
            'service_id' => $appointment->serviceId(),
            'service_name' => $appointment->serviceName(),
            'scheduled_at' => $appointment->scheduledAt()->format(DATE_ATOM),
            'status' => $appointment->status()->value,
        ];
    }
}
