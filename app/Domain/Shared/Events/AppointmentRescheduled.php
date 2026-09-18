<?php

declare(strict_types=1);

namespace App\Domain\Shared\Events;

use App\Domain\Appointment\ReminderPreferences;
use App\Domain\Shared\DomainEvent;
use DateTimeImmutable;

final class AppointmentRescheduled implements DomainEvent
{
    public function __construct(
        private readonly int $appointmentId,
        private readonly int $clientId,
        private readonly int $serviceId,
        private readonly DateTimeImmutable $oldScheduledAt,
        private readonly DateTimeImmutable $newScheduledAt,
        private readonly ?int $oldEmployeeId,
        private readonly ?int $newEmployeeId,
        private readonly ReminderPreferences $reminderPreferences,
        private readonly DateTimeImmutable $occurredOn = new DateTimeImmutable(),
    ) {
    }

    public function appointmentId(): int
    {
        return $this->appointmentId;
    }

    public function clientId(): int
    {
        return $this->clientId;
    }

    public function serviceId(): int
    {
        return $this->serviceId;
    }

    public function oldScheduledAt(): DateTimeImmutable
    {
        return $this->oldScheduledAt;
    }

    public function newScheduledAt(): DateTimeImmutable
    {
        return $this->newScheduledAt;
    }

    public function oldEmployeeId(): ?int
    {
        return $this->oldEmployeeId;
    }

    public function newEmployeeId(): ?int
    {
        return $this->newEmployeeId;
    }

    public function reminderPreferences(): ReminderPreferences
    {
        return $this->reminderPreferences;
    }

    public function occurredOn(): DateTimeImmutable
    {
        return $this->occurredOn;
    }
}
