<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers\Appointment;

use App\Application\Appointment\Exceptions\AppointmentNotFoundException;
use App\Application\Appointment\UseCases\ConfirmAppointmentUseCase;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;

final class ConfirmAppointmentController
{
    public function __construct(
        private readonly ConfirmAppointmentUseCase $useCase,
    ) {
    }

    public function __invoke(string $token): JsonResponse
    {
        try {
            $appointment = $this->useCase->execute($token);
        } catch (AppointmentNotFoundException) {
            return response()->json(['message' => 'Appointment not found.'], 404);
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
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
