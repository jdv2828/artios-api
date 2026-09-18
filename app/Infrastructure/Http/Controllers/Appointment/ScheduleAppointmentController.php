<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers\Appointment;

use App\Application\Appointment\Exceptions\NoEmployeeAvailableException;
use App\Application\Appointment\UseCases\ScheduleAppointmentUseCase;
use App\Infrastructure\Http\Requests\Appointment\ScheduleAppointmentRequest;
use Illuminate\Http\JsonResponse;

final class ScheduleAppointmentController
{
    public function __construct(
        private readonly ScheduleAppointmentUseCase $useCase,
    ) {
    }

    public function __invoke(ScheduleAppointmentRequest $request): JsonResponse
    {
        try {
            $appointment = $this->useCase->execute($request->toDto());
        } catch (NoEmployeeAvailableException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
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
        ], 201);
    }
}
