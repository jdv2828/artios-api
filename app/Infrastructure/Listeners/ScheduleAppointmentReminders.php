<?php

declare(strict_types=1);

namespace App\Infrastructure\Listeners;

use App\Infrastructure\Events\LaravelAppointmentScheduled;
use App\Infrastructure\Jobs\SendAppointmentReminder;
use DateTimeImmutable;

final class ScheduleAppointmentReminders
{
    public function handle(LaravelAppointmentScheduled $event): void
    {
        if ($event->reminderPreferences->remind1DayBefore()) {
            $this->dispatchIfFuture($event->appointmentId, '1_day', $event->scheduledAt->modify('-1 day'));
        }

        if ($event->reminderPreferences->remind1HourBefore()) {
            $this->dispatchIfFuture($event->appointmentId, '1_hour', $event->scheduledAt->modify('-1 hour'));
        }

        if ($event->reminderPreferences->remind30MinsBefore()) {
            $this->dispatchIfFuture($event->appointmentId, '30_mins', $event->scheduledAt->modify('-30 minutes'));
        }
    }

    private function dispatchIfFuture(int $appointmentId, string $reminderType, DateTimeImmutable $delayAt): void
    {
        if ($delayAt <= new DateTimeImmutable()) {
            return;
        }

        SendAppointmentReminder::dispatch($appointmentId, $reminderType)->delay($delayAt);
    }
}
