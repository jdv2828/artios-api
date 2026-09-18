<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers\Appointment;

use App\Application\Appointment\Exceptions\AppointmentNotFoundException;
use App\Application\Appointment\UseCases\ChangeAppointmentStatusUseCase;
use App\Application\Appointment\UseCases\GetAppointmentUseCase;
use App\Application\Appointment\UseCases\ListAppointmentsUseCase;
use App\Infrastructure\Http\Requests\Appointment\ChangeAppointmentStatusRequest;
use App\Infrastructure\Http\Requests\Appointment\ListAppointmentsRequest;
use App\Infrastructure\Persistence\Eloquent\Models\ClientModel;
use Illuminate\Http\JsonResponse;

final class AppointmentController
{
    public function __construct(
        private readonly ListAppointmentsUseCase $listAppointmentsUseCase,
        private readonly GetAppointmentUseCase $getAppointmentUseCase,
        private readonly ChangeAppointmentStatusUseCase $changeAppointmentStatusUseCase,
    ) {
    }

    public function index(ListAppointmentsRequest $request): JsonResponse
    {
        $result = $this->listAppointmentsUseCase->execute($request->toDto());

        $clientMap = $this->buildClientMap($result->items);

        return response()->json([
            'data' => array_map(function (object $appointment) use ($clientMap): array {
                $client = $clientMap[(int) $appointment->clientId()] ?? null;

                return $this->normalize($appointment, $client);
            }, $result->items),
            'meta' => [
                'total' => $result->total,
                'page' => $result->page,
                'per_page' => $result->perPage,
                'last_page' => $result->lastPage,
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        try {
            $appointment = $this->getAppointmentUseCase->execute($id);
        } catch (AppointmentNotFoundException) {
            return response()->json(['message' => 'Appointment not found.'], 404);
        }

        $client = $this->getClientData($appointment->clientId());

        return response()->json(['data' => $this->normalize($appointment, $client)]);
    }

    public function update(ChangeAppointmentStatusRequest $request, int $id): JsonResponse
    {
        try {
            $appointment = $this->changeAppointmentStatusUseCase->execute($request->toDto($id));
        } catch (AppointmentNotFoundException) {
            return response()->json(['message' => 'Appointment not found.'], 404);
        } catch (\InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $client = $this->getClientData($appointment->clientId());

        return response()->json(['data' => $this->normalize($appointment, $client)]);
    }

    private function normalize(object $appointment, ?array $client = null): array
    {
        return [
            'id' => $appointment->id(),
            'client_id' => $appointment->clientId(),
            'client_name' => $client['client_name'] ?? null,
            'client_dni' => $client['client_dni'] ?? null,
            'employee_id' => $appointment->employeeId(),
            'service_id' => $appointment->serviceId(),
            'scheduled_at' => $appointment->scheduledAt()->format(DATE_ATOM),
            'status' => $appointment->status()->value,
            'reminders' => [
                'remind_1_day_before' => $appointment->reminderPreferences()->remind1DayBefore(),
                'remind_30_mins_before' => $appointment->reminderPreferences()->remind30MinsBefore(),
                'remind_1_hour_before' => $appointment->reminderPreferences()->remind1HourBefore(),
            ],
        ];
    }

    /**
     * @param object[] $appointments
     * @return array<int, array{client_name: string, client_dni: string}>
     */
    private function buildClientMap(array $appointments): array
    {
        $clientIds = array_values(array_unique(array_map(fn (object $a): int => (int) $a->clientId(), $appointments)));
        if ($clientIds === []) {
            return [];
        }

        $clients = ClientModel::query()
            ->whereIn('id', $clientIds)
            ->get(['id', 'first_name', 'last_name', 'dni']);

        $map = [];
        foreach ($clients as $client) {
            $name = trim(((string) $client->first_name).' '.((string) $client->last_name));
            $map[(int) $client->id] = [
                'client_name' => $name,
                'client_dni' => (string) $client->dni,
            ];
        }

        return $map;
    }

    private function getClientData(int $clientId): ?array
    {
        $client = ClientModel::query()->find($clientId, ['id', 'first_name', 'last_name', 'dni']);
        if ($client === null) {
            return null;
        }

        return [
            'client_name' => trim(((string) $client->first_name).' '.((string) $client->last_name)),
            'client_dni' => (string) $client->dni,
        ];
    }
}
