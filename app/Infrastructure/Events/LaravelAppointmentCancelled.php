<?php

declare(strict_types=1);

namespace App\Infrastructure\Events;

use DateTimeImmutable;
use Illuminate\Foundation\Events\Dispatchable;

final class LaravelAppointmentCancelled
{
    use Dispatchable;

    public function __construct(
        public readonly int $appointmentId,
        public readonly int $clientId,
        public readonly int $serviceId,
        public readonly DateTimeImmutable $scheduledAt,
    ) {
    }
}
