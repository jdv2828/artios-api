<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Requests\Appointment;

use App\Application\Appointment\DTO\ScheduleAppointmentDTO;
use DateTimeImmutable;
use Illuminate\Foundation\Http\FormRequest;

final class ScheduleAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'dni' => ['required', 'string', 'regex:/^\d{7,10}$/'],
            'phone' => ['required', 'string', 'regex:/^\+?[0-9\-\s]{6,20}$/'],
            'email' => ['required', 'email', 'max:255'],
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'scheduled_at' => ['required', 'date', 'after:now'],
            'remind_1_day_before' => ['required', 'boolean'],
            'remind_30_mins_before' => ['required', 'boolean'],
        ];
    }

    public function toDto(): ScheduleAppointmentDTO
    {
        $validated = $this->validated();

        return new ScheduleAppointmentDTO(
            firstName: (string) $validated['first_name'],
            lastName: (string) $validated['last_name'],
            dni: (string) $validated['dni'],
            phone: (string) $validated['phone'],
            email: (string) $validated['email'],
            serviceId: (int) $validated['service_id'],
            scheduledAt: new DateTimeImmutable((string) $validated['scheduled_at']),
            remind1DayBefore: (bool) $validated['remind_1_day_before'],
            remind30MinsBefore: (bool) $validated['remind_30_mins_before'],
        );
    }
}
