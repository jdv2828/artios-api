<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Requests\Appointment;

use App\Application\Appointment\DTO\RescheduleAppointmentDTO;
use DateTimeImmutable;
use Illuminate\Foundation\Http\FormRequest;

final class RescheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'scheduled_at' => ['required', 'date', 'after:now'],
            'employee_id' => ['required', 'integer', 'min:1'],
        ];
    }

    public function toDto(): RescheduleAppointmentDTO
    {
        $validated = $this->validated();

        return new RescheduleAppointmentDTO(
            appointmentId: (int) $this->route('id'),
            scheduledAt: new DateTimeImmutable((string) $validated['scheduled_at']),
            employeeId: (int) $validated['employee_id'],
        );
    }
}
