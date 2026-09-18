<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Requests\Appointment;

use App\Application\Appointment\DTO\ChangeAppointmentStatusDTO;
use App\Domain\Appointment\AppointmentStatus;
use Illuminate\Foundation\Http\FormRequest;

final class ChangeAppointmentStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:confirmed,cancelled,completed,no_show'],
        ];
    }

    public function toDto(int $appointmentId): ChangeAppointmentStatusDTO
    {
        $validated = $this->validated();

        return new ChangeAppointmentStatusDTO(
            appointmentId: $appointmentId,
            status: AppointmentStatus::from((string) $validated['status']),
        );
    }
}
