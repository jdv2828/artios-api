<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Adapters;

use App\Application\Appointment\DTO\AppointmentSearchResultDTO;
use App\Application\Appointment\DTO\ListAppointmentsDTO;
use App\Application\Appointment\Ports\AppointmentRepositoryInterface;
use App\Domain\Appointment\Appointment;
use App\Domain\Appointment\AppointmentStatus;
use App\Domain\Appointment\ReminderPreferences;
use App\Domain\Shared\Events\AppointmentCancelled as DomainAppointmentCancelled;
use App\Domain\Shared\Events\AppointmentConfirmed as DomainAppointmentConfirmed;
use App\Domain\Shared\Events\AppointmentRescheduled as DomainAppointmentRescheduled;
use App\Domain\Shared\Events\AppointmentScheduled as DomainAppointmentScheduled;
use App\Infrastructure\Events\LaravelAppointmentCancelled;
use App\Infrastructure\Events\LaravelAppointmentConfirmed;
use App\Infrastructure\Events\LaravelAppointmentRescheduled;
use App\Infrastructure\Events\LaravelAppointmentScheduled;
use App\Infrastructure\Persistence\Eloquent\Models\AppointmentModel;
use Illuminate\Support\Facades\Event;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use RuntimeException;

final class EloquentAppointmentRepository implements AppointmentRepositoryInterface
{
    public function findById(int $id): ?Appointment
    {
        $model = AppointmentModel::query()
            ->with(['client', 'service'])
            ->find($id);

        return $model ? $this->toDomain($model) : null;
    }

    public function findAllFiltered(ListAppointmentsDTO $criteria): AppointmentSearchResultDTO
    {
        $query = AppointmentModel::query()->with(['client', 'service']);

        if ($criteria->scheduledDate !== null) {
            $query->whereDate('scheduled_at', $criteria->scheduledDate);
        } elseif ($criteria->scheduledDateFrom !== null || $criteria->scheduledDateTo !== null) {
            if ($criteria->scheduledDateFrom !== null) {
                $query->where('scheduled_at', '>=', $criteria->scheduledDateFrom);
            }
            if ($criteria->scheduledDateTo !== null) {
                $query->where('scheduled_at', '<=', $criteria->scheduledDateTo . ' 23:59:59');
            }
        }

        if ($criteria->status !== null) {
            $query->where('status', $criteria->status);
        }

        if ($criteria->clientDni !== null) {
            $query->whereHas('client', function (Builder $builder) use ($criteria): void {
                $builder->where('dni', 'like', $criteria->clientDni.'%');
            });
        }

        if ($criteria->clientName !== null) {
            $query->whereHas('client', function (Builder $builder) use ($criteria): void {
                $builder->where('first_name', 'like', $criteria->clientName.'%')
                    ->orWhere('last_name', 'like', $criteria->clientName.'%');
            });
        }

        if ($criteria->employeeId !== null) {
            $query->where('employee_id', $criteria->employeeId);
        }

        $paginator = $query
            ->orderByDesc('scheduled_at')
            ->paginate(perPage: $criteria->perPage, page: $criteria->page);

        return new AppointmentSearchResultDTO(
            items: $paginator->getCollection()->map(fn (AppointmentModel $model): Appointment => $this->toDomain($model))->all(),
            total: $paginator->total(),
            page: $paginator->currentPage(),
            perPage: $paginator->perPage(),
            lastPage: $paginator->lastPage(),
        );
    }

    public function findByClientId(int $clientId): array
    {
        return AppointmentModel::query()
            ->with(['client', 'service'])
            ->where('client_id', $clientId)
            ->orderByDesc('scheduled_at')
            ->get()
            ->map(fn (AppointmentModel $model): Appointment => $this->toDomain($model))
            ->all();
    }

    public function findByConfirmationToken(string $token): ?Appointment
    {
        $model = AppointmentModel::query()
            ->with(['client', 'service'])
            ->where('confirmation_token', $token)
            ->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function save(Appointment $appointment): Appointment
    {
        if ($appointment->id() === null) {
            $confirmationToken = $appointment->confirmationToken() ?? (string) Str::uuid();
            $model = AppointmentModel::query()->create([
                'client_id' => $appointment->clientId(),
                'employee_id' => $appointment->employeeId(),
                'service_id' => $appointment->serviceId(),
                'scheduled_at' => $appointment->scheduledAt(),
                'status' => $appointment->status()->value,
                'confirmation_token' => $confirmationToken,
                'remind_1_day_before' => $appointment->reminderPreferences()->remind1DayBefore(),
                'remind_30_mins_before' => $appointment->reminderPreferences()->remind30MinsBefore(),
                'remind_1_hour_before' => $appointment->reminderPreferences()->remind1HourBefore(),
            ]);
            $appointment->assignConfirmationToken($confirmationToken);
        } else {
            $model = AppointmentModel::query()->find($appointment->id());

            if ($model === null) {
                throw new RuntimeException('Unable to save appointment.');
            }

            $model->fill([
                'client_id' => $appointment->clientId(),
                'employee_id' => $appointment->employeeId(),
                'service_id' => $appointment->serviceId(),
                'scheduled_at' => $appointment->scheduledAt(),
                'status' => $appointment->status()->value,
                'confirmation_token' => $appointment->confirmationToken(),
                'remind_1_day_before' => $appointment->reminderPreferences()->remind1DayBefore(),
                'remind_30_mins_before' => $appointment->reminderPreferences()->remind30MinsBefore(),
                'remind_1_hour_before' => $appointment->reminderPreferences()->remind1HourBefore(),
            ]);
            $model->save();
        }

        $appointment->assignId((int) $model->id);
        if (is_string($model->confirmation_token) && $model->confirmation_token !== '') {
            $appointment->assignConfirmationToken($model->confirmation_token);
        }

        foreach ($appointment->pullDomainEvents() as $domainEvent) {
            match (true) {
                $domainEvent instanceof DomainAppointmentScheduled => Event::dispatch(new LaravelAppointmentScheduled(
                    appointmentId: (int) $model->id,
                    clientId: $domainEvent->clientId(),
                    serviceId: $domainEvent->serviceId(),
                    scheduledAt: $domainEvent->scheduledAt(),
                    reminderPreferences: $domainEvent->reminderPreferences(),
                )),
                $domainEvent instanceof DomainAppointmentRescheduled => Event::dispatch(new LaravelAppointmentRescheduled(
                    appointmentId: (int) $model->id,
                    clientId: $domainEvent->clientId(),
                    serviceId: $domainEvent->serviceId(),
                    oldScheduledAt: $domainEvent->oldScheduledAt(),
                    newScheduledAt: $domainEvent->newScheduledAt(),
                    oldEmployeeId: $domainEvent->oldEmployeeId(),
                    newEmployeeId: $domainEvent->newEmployeeId(),
                    reminderPreferences: $domainEvent->reminderPreferences(),
                )),
                $domainEvent instanceof DomainAppointmentConfirmed => Event::dispatch(new LaravelAppointmentConfirmed(
                    appointmentId: (int) $model->id,
                    clientId: $domainEvent->clientId(),
                    serviceId: $domainEvent->serviceId(),
                    scheduledAt: $domainEvent->scheduledAt(),
                )),
                $domainEvent instanceof DomainAppointmentCancelled => Event::dispatch(new LaravelAppointmentCancelled(
                    appointmentId: (int) $model->id,
                    clientId: $domainEvent->clientId(),
                    serviceId: $domainEvent->serviceId(),
                    scheduledAt: $domainEvent->scheduledAt(),
                )),
                default => throw new RuntimeException('Unsupported domain event dispatched from Appointment.'),
            };
        }

        return $appointment;
    }

    private function toDomain(AppointmentModel $model): Appointment
    {
        return Appointment::rehydrate(
            id: (int) $model->id,
            clientId: (int) $model->client_id,
            employeeId: $model->employee_id !== null ? (int) $model->employee_id : null,
            serviceId: (int) $model->service_id,
            serviceName: $model->relationLoaded('service') && $model->service !== null ? (string) $model->service->name : null,
            scheduledAt: $model->scheduled_at instanceof \DateTimeInterface
                ? \DateTimeImmutable::createFromInterface($model->scheduled_at)
                : new \DateTimeImmutable((string) $model->scheduled_at),
            status: AppointmentStatus::from((string) $model->status),
            reminderPreferences: new ReminderPreferences(
                remind1DayBefore: (bool) $model->remind_1_day_before,
                remind30MinsBefore: (bool) $model->remind_30_mins_before,
                remind1HourBefore: (bool) $model->remind_1_hour_before,
            ),
            confirmationToken: $model->confirmation_token !== null ? (string) $model->confirmation_token : null,
        );
    }
}
