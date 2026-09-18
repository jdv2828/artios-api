<?php

declare(strict_types=1);

namespace App\Domain\Appointment;

use App\Domain\Shared\DomainEvent;
use App\Domain\Shared\Events\AppointmentCancelled;
use App\Domain\Shared\Events\AppointmentConfirmed;
use App\Domain\Shared\Events\AppointmentRescheduled;
use App\Domain\Shared\Events\AppointmentScheduled;
use DateTimeImmutable;
use InvalidArgumentException;

final class Appointment
{
    /**
     * @var DomainEvent[]
     */
    private array $domainEvents = [];

    private function __construct(
        private ?int $id,
        private int $clientId,
        private ?int $employeeId,
        private int $serviceId,
        private ?string $serviceName = null,
        private DateTimeImmutable $scheduledAt,
        private AppointmentStatus $status,
        private ReminderPreferences $reminderPreferences,
        private ?string $confirmationToken = null,
        private bool $ensureFutureScheduledAt = true,
    ) {
        if ($this->id !== null && $this->id <= 0) {
            throw new InvalidArgumentException('Appointment id must be greater than zero.');
        }

        if ($this->clientId <= 0) {
            throw new InvalidArgumentException('Client id must be greater than zero.');
        }

        if ($this->employeeId !== null && $this->employeeId <= 0) {
            throw new InvalidArgumentException('Employee id must be greater than zero.');
        }

        if ($this->serviceId <= 0) {
            throw new InvalidArgumentException('Service id must be greater than zero.');
        }

        if ($this->ensureFutureScheduledAt && $this->scheduledAt <= new DateTimeImmutable()) {
            throw new InvalidArgumentException('Appointment datetime must be in the future.');
        }
    }

    public static function schedule(
        int $clientId,
        int $employeeId,
        int $serviceId,
        DateTimeImmutable $scheduledAt,
        ReminderPreferences $reminderPreferences,
    ): self {
        $appointment = new self(
            id: null,
            clientId: $clientId,
            employeeId: $employeeId,
            serviceId: $serviceId,
            serviceName: null,
            scheduledAt: $scheduledAt,
            status: AppointmentStatus::Scheduled,
            reminderPreferences: $reminderPreferences,
            confirmationToken: null,
            ensureFutureScheduledAt: true,
        );

        $appointment->record(
            new AppointmentScheduled(
                appointmentId: $appointment->id,
                clientId: $clientId,
                serviceId: $serviceId,
                scheduledAt: $scheduledAt,
                reminderPreferences: $reminderPreferences,
            )
        );

        return $appointment;
    }

    public static function rehydrate(
        int $id,
        int $clientId,
        ?int $employeeId,
        int $serviceId,
        ?string $serviceName = null,
        DateTimeImmutable $scheduledAt,
        AppointmentStatus $status,
        ReminderPreferences $reminderPreferences,
        ?string $confirmationToken = null,
    ): self {
        return new self(
            id: $id,
            clientId: $clientId,
            employeeId: $employeeId,
            serviceId: $serviceId,
            serviceName: $serviceName,
            scheduledAt: $scheduledAt,
            status: $status,
            reminderPreferences: $reminderPreferences,
            confirmationToken: $confirmationToken,
            ensureFutureScheduledAt: false,
        );
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function clientId(): int
    {
        return $this->clientId;
    }

    public function serviceId(): int
    {
        return $this->serviceId;
    }

    public function serviceName(): ?string
    {
        return $this->serviceName;
    }

    public function employeeId(): ?int
    {
        return $this->employeeId;
    }

    public function scheduledAt(): DateTimeImmutable
    {
        return $this->scheduledAt;
    }

    public function status(): AppointmentStatus
    {
        return $this->status;
    }

    public function reminderPreferences(): ReminderPreferences
    {
        return $this->reminderPreferences;
    }

    public function confirmationToken(): ?string
    {
        return $this->confirmationToken;
    }

    public function confirm(): void
    {
        if ($this->status !== AppointmentStatus::Scheduled) {
            throw new InvalidArgumentException('Only scheduled appointments can be confirmed.');
        }

        $this->status = AppointmentStatus::Confirmed;

        $this->record(
            new AppointmentConfirmed(
                appointmentId: $this->id,
                clientId: $this->clientId,
                serviceId: $this->serviceId,
                scheduledAt: $this->scheduledAt,
            )
        );
    }

    public function cancel(): void
    {
        if (! in_array($this->status, [AppointmentStatus::Scheduled, AppointmentStatus::Confirmed], true)) {
            throw new InvalidArgumentException('Only scheduled or confirmed appointments can be cancelled.');
        }

        $this->status = AppointmentStatus::Cancelled;

        $this->record(
            new AppointmentCancelled(
                appointmentId: $this->id,
                clientId: $this->clientId,
                serviceId: $this->serviceId,
                scheduledAt: $this->scheduledAt,
            )
        );
    }

    public function complete(): void
    {
        if ($this->status !== AppointmentStatus::Confirmed) {
            throw new InvalidArgumentException('Only confirmed appointments can be completed.');
        }

        $this->status = AppointmentStatus::Completed;
    }

    public function markNoShow(): void
    {
        if ($this->status !== AppointmentStatus::Confirmed) {
            throw new InvalidArgumentException('Only confirmed appointments can be marked as no show.');
        }

        $this->status = AppointmentStatus::NoShow;
    }

    public function reschedule(DateTimeImmutable $newScheduledAt, int $newEmployeeId): AppointmentRescheduled
    {
        if (! in_array($this->status, [AppointmentStatus::Scheduled, AppointmentStatus::Confirmed], true)) {
            throw new InvalidArgumentException('Only scheduled or confirmed appointments can be rescheduled.');
        }

        if ($newScheduledAt <= new DateTimeImmutable()) {
            throw new InvalidArgumentException('Rescheduled datetime must be in the future.');
        }

        $oldScheduledAt = $this->scheduledAt;
        $oldEmployeeId = $this->employeeId;

        $this->scheduledAt = $newScheduledAt;
        $this->employeeId = $newEmployeeId;

        $event = new AppointmentRescheduled(
            appointmentId: $this->id ?? 0,
            clientId: $this->clientId,
            serviceId: $this->serviceId,
            oldScheduledAt: $oldScheduledAt,
            newScheduledAt: $newScheduledAt,
            oldEmployeeId: $oldEmployeeId,
            newEmployeeId: $newEmployeeId,
            reminderPreferences: $this->reminderPreferences,
        );

        $this->record($event);

        return $event;
    }

    public function assignId(int $id): void
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('Appointment id must be greater than zero.');
        }

        $this->id = $id;
    }

    public function assignConfirmationToken(string $confirmationToken): void
    {
        $confirmationToken = trim($confirmationToken);

        if ($confirmationToken === '') {
            throw new InvalidArgumentException('Confirmation token cannot be empty.');
        }

        $this->confirmationToken = $confirmationToken;
    }

    /**
     * @return DomainEvent[]
     */
    public function pullDomainEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];

        return $events;
    }

    private function record(DomainEvent $event): void
    {
        $this->domainEvents[] = $event;
    }
}
