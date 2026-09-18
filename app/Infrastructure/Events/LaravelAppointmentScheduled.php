<?php

declare(strict_types=1);

namespace App\Infrastructure\Events;

use App\Domain\Appointment\ReminderPreferences;
use DateTimeImmutable;
use Illuminate\Foundation\Events\Dispatchable;

final class LaravelAppointmentScheduled
{
    use Dispatchable;

    public function __construct(
        public readonly int $appointmentId,
        public readonly int $clientId,
        public readonly int $serviceId,
        public readonly DateTimeImmutable $scheduledAt,
        public readonly ReminderPreferences $reminderPreferences,
    ) {
    }
}
