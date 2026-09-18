<?php

declare(strict_types=1);

namespace App\Infrastructure\Events;

use App\Domain\Appointment\ReminderPreferences;
use DateTimeImmutable;
use Illuminate\Foundation\Events\Dispatchable;

final class LaravelAppointmentRescheduled
{
    use Dispatchable;

    public function __construct(
        public readonly int $appointmentId,
        public readonly int $clientId,
        public readonly int $serviceId,
        public readonly DateTimeImmutable $oldScheduledAt,
        public readonly DateTimeImmutable $newScheduledAt,
        public readonly ?int $oldEmployeeId,
        public readonly ?int $newEmployeeId,
        public readonly ReminderPreferences $reminderPreferences,
    ) {
    }
}
