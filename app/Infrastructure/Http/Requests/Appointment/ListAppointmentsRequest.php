<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Requests\Appointment;

use App\Application\Appointment\DTO\ListAppointmentsDTO;
use App\Domain\User\UserRole;
use Illuminate\Foundation\Http\FormRequest;

final class ListAppointmentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isAdmin = $this->user()?->role === UserRole::Admin;

        return [
            'scheduled_date' => ['nullable', 'date'],
            'scheduled_date_from' => ['nullable', 'date'],
            'scheduled_date_to' => ['nullable', 'date', 'after_or_equal:scheduled_date_from'],
            'status' => ['nullable', 'in:scheduled,confirmed,cancelled,completed,no_show'],
            'client_dni' => ['nullable', 'string', 'regex:/^\d{1,10}$/'],
            'client_name' => ['nullable', 'string', 'max:100'],
            'employee_id' => ['nullable', 'integer', 'min:1'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:' . ($isAdmin ? 500 : 100)],
        ];
    }

    public function toDto(): ListAppointmentsDTO
    {
        $validated = $this->validated();

        $employeeId = isset($validated['employee_id']) ? (int) $validated['employee_id'] : null;
        $user = $this->user();
        if ($user !== null && ($user->role ?? null) === UserRole::Employee) {
            // Employees can only see their own appointments.
            $employeeId = (int) $user->id;
        }

        return new ListAppointmentsDTO(
            scheduledDate: $validated['scheduled_date'] ?? null,
            scheduledDateFrom: $validated['scheduled_date_from'] ?? null,
            scheduledDateTo: $validated['scheduled_date_to'] ?? null,
            status: $validated['status'] ?? null,
            clientDni: $validated['client_dni'] ?? null,
            clientName: $validated['client_name'] ?? null,
            employeeId: $employeeId,
            page: isset($validated['page']) ? (int) $validated['page'] : 1,
            perPage: isset($validated['per_page']) ? (int) $validated['per_page'] : 15,
        );
    }
}
