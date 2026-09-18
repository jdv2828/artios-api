<?php

declare(strict_types=1);

namespace App\Infrastructure\Listeners;

use App\Infrastructure\Events\LaravelAppointmentRescheduled;
use App\Infrastructure\Jobs\SendAppointmentReminder;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;

final class RescheduleAppointmentReminders
{
    public function handle(LaravelAppointmentRescheduled $event): void
    {
        // Cancel old reminder jobs by matching payload containing the appointment id.
        DB::table('jobs')
            ->where('payload', 'like', '%"appointmentId":'.$event->appointmentId.'%')
            ->delete();

        // Re-dispatch reminders with new timings.
        if ($event->reminderPreferences->remind1DayBefore()) {
            $this->dispatchIfFuture($event->appointmentId, '1_day', $event->newScheduledAt->modify('-1 day'));
        }

        if ($event->reminderPreferences->remind1HourBefore()) {
            $this->dispatchIfFuture($event->appointmentId, '1_hour', $event->newScheduledAt->modify('-1 hour'));
        }

        if ($event->reminderPreferences->remind30MinsBefore()) {
            $this->dispatchIfFuture($event->appointmentId, '30_mins', $event->newScheduledAt->modify('-30 minutes'));
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
