<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Appointment\ReminderPreferences;
use App\Domain\Shared\Events\AppointmentRescheduled;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class AppointmentRescheduledTest extends TestCase
{
    public function test_it_carries_correct_payload(): void
    {
        $oldScheduledAt = new DateTimeImmutable('2026-09-01 10:00:00');
        $newScheduledAt = new DateTimeImmutable('2026-09-02 14:00:00');
        $reminderPreferences = new ReminderPreferences(true, true);

        $event = new AppointmentRescheduled(
            appointmentId: 42,
            clientId: 7,
            serviceId: 3,
            oldScheduledAt: $oldScheduledAt,
            newScheduledAt: $newScheduledAt,
            oldEmployeeId: 10,
            newEmployeeId: 11,
            reminderPreferences: $reminderPreferences,
        );

        self::assertSame(42, $event->appointmentId());
        self::assertSame(7, $event->clientId());
        self::assertSame(3, $event->serviceId());
        self::assertSame($oldScheduledAt, $event->oldScheduledAt());
        self::assertSame($newScheduledAt, $event->newScheduledAt());
        self::assertSame(10, $event->oldEmployeeId());
        self::assertSame(11, $event->newEmployeeId());
        self::assertSame($reminderPreferences, $event->reminderPreferences());
        self::assertInstanceOf(DateTimeImmutable::class, $event->occurredOn());
    }

    public function test_it_handles_null_employee_ids(): void
    {
        $event = new AppointmentRescheduled(
            appointmentId: 1,
            clientId: 2,
            serviceId: 3,
            oldScheduledAt: new DateTimeImmutable('+1 day'),
            newScheduledAt: new DateTimeImmutable('+2 day'),
            oldEmployeeId: null,
            newEmployeeId: null,
            reminderPreferences: new ReminderPreferences(false, false),
        );

        self::assertNull($event->oldEmployeeId());
        self::assertNull($event->newEmployeeId());
    }
}
