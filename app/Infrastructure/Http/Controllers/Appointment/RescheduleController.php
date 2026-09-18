<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers\Appointment;

use App\Application\Appointment\Exceptions\AppointmentNotFoundException;
use App\Application\Appointment\Exceptions\SlotUnavailableException;
use App\Application\Appointment\UseCases\RescheduleAppointmentUseCase;
use App\Infrastructure\Http\Requests\Appointment\RescheduleRequest;
use Illuminate\Http\JsonResponse;

final class RescheduleController
{
    public function __construct(
        private readonly RescheduleAppointmentUseCase $useCase,
    ) {
    }

    public function __invoke(RescheduleRequest $request): JsonResponse
    {
        try {
            $appointment = $this->useCase->execute($request->toDto());
        } catch (AppointmentNotFoundException) {
            return response()->json(['message' => 'Appointment not found.'], 404);
        } catch (SlotUnavailableException $exception) {
            return response()->json(['message' => $exception->getMessage()], 409);
        }

        return response()->json([
            'data' => [
                'id' => $appointment->id(),
                'client_id' => $appointment->clientId(),
                'employee_id' => $appointment->employeeId(),
                'service_id' => $appointment->serviceId(),
                'scheduled_at' => $appointment->scheduledAt()->format(DATE_ATOM),
                'status' => $appointment->status()->value,
                'reminders' => [
                    'remind_1_day_before' => $appointment->reminderPreferences()->remind1DayBefore(),
                    'remind_30_mins_before' => $appointment->reminderPreferences()->remind30MinsBefore(),
                    'remind_1_hour_before' => $appointment->reminderPreferences()->remind1HourBefore(),
                ],
            ],
        ]);
    }
}
