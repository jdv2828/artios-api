<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Appointment\Appointment;
use App\Domain\Appointment\AppointmentStatus;
use App\Domain\Appointment\ReminderPreferences;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class AppointmentTest extends TestCase
{
    public function test_it_confirms_a_scheduled_appointment(): void
    {
        $appointment = $this->scheduledAppointment();

        $appointment->confirm();

        self::assertSame(AppointmentStatus::Confirmed, $appointment->status());
    }

    public function test_it_cancels_a_scheduled_appointment(): void
    {
        $appointment = $this->scheduledAppointment();

        $appointment->cancel();

        self::assertSame(AppointmentStatus::Cancelled, $appointment->status());
    }

    public function test_it_marks_no_show_only_when_confirmed(): void
    {
        $appointment = $this->scheduledAppointment();
        $appointment->confirm();
        $appointment->markNoShow();

        self::assertSame(AppointmentStatus::NoShow, $appointment->status());
    }

    public function test_it_rejects_completion_when_not_confirmed(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->scheduledAppointment()->complete();
    }

    private function scheduledAppointment(): Appointment
    {
        return Appointment::schedule(
            clientId: 1,
            employeeId: 1,
            serviceId: 1,
            scheduledAt: new DateTimeImmutable('+1 day'),
            reminderPreferences: new ReminderPreferences(true, false),
        );
    }
}
