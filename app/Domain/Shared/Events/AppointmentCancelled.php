<?php

declare(strict_types=1);

namespace App\Domain\Shared\Events;

use App\Domain\Shared\DomainEvent;
use DateTimeImmutable;

final class AppointmentCancelled implements DomainEvent
{
    public function __construct(
        private readonly ?int $appointmentId,
        private readonly int $clientId,
        private readonly int $serviceId,
        private readonly DateTimeImmutable $scheduledAt,
        private readonly DateTimeImmutable $occurredOn = new DateTimeImmutable(),
    ) {
    }

    public function appointmentId(): ?int
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

    public function scheduledAt(): DateTimeImmutable
    {
        return $this->scheduledAt;
    }

    public function occurredOn(): DateTimeImmutable
    {
        return $this->occurredOn;
    }
}
