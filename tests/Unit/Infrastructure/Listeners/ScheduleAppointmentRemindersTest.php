<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Listeners;

use App\Domain\Appointment\ReminderPreferences;
use App\Infrastructure\Events\LaravelAppointmentScheduled;
use App\Infrastructure\Jobs\SendAppointmentReminder;
use App\Infrastructure\Listeners\ScheduleAppointmentReminders;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

final class ScheduleAppointmentRemindersTest extends TestCase
{
    public function test_it_dispatches_three_jobs_for_future_reminders(): void
    {
        Bus::fake();

        $scheduledAt = new \DateTimeImmutable('+1 day +2 hours');

        (new ScheduleAppointmentReminders())->handle(new LaravelAppointmentScheduled(
            appointmentId: 10,
            clientId: 20,
            serviceId: 30,
            scheduledAt: $scheduledAt,
            reminderPreferences: new ReminderPreferences(true, true),
        ));

        Bus::assertDispatchedTimes(SendAppointmentReminder::class, 3);
        Bus::assertDispatched(SendAppointmentReminder::class, function (SendAppointmentReminder $job) use ($scheduledAt): bool {
            return $job->reminderType === '1_day' && $job->delay == $scheduledAt->modify('-1 day');
        });
        Bus::assertDispatched(SendAppointmentReminder::class, function (SendAppointmentReminder $job) use ($scheduledAt): bool {
            return $job->reminderType === '1_hour' && $job->delay == $scheduledAt->modify('-1 hour');
        });
        Bus::assertDispatched(SendAppointmentReminder::class, function (SendAppointmentReminder $job) use ($scheduledAt): bool {
            return $job->reminderType === '30_mins' && $job->delay == $scheduledAt->modify('-30 minutes');
        });
    }

    public function test_it_respects_reminder_preferences(): void
    {
        Bus::fake();

        $scheduledAt = new \DateTimeImmutable('+1 day +2 hours');

        (new ScheduleAppointmentReminders())->handle(new LaravelAppointmentScheduled(
            appointmentId: 10,
            clientId: 20,
            serviceId: 30,
            scheduledAt: $scheduledAt,
            reminderPreferences: new ReminderPreferences(false, false),
        ));

        // Default remind_1_hour_before is true.
        Bus::assertDispatchedTimes(SendAppointmentReminder::class, 1);
        Bus::assertDispatched(SendAppointmentReminder::class, function (SendAppointmentReminder $job) use ($scheduledAt): bool {
            return $job->reminderType === '1_hour' && $job->delay == $scheduledAt->modify('-1 hour');
        });
    }
}
