<?php

declare(strict_types=1);

namespace App\Infrastructure\Listeners;

use App\Infrastructure\Events\LaravelAppointmentRescheduled;
use App\Infrastructure\Mail\AppointmentRescheduledMail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

final class SendRescheduleNotificationToOldProfessional
{
    public function handle(LaravelAppointmentRescheduled $event): void
    {
        if ($event->oldEmployeeId === null || $event->oldEmployeeId === $event->newEmployeeId) {
            return;
        }

        $employee = User::query()->find($event->oldEmployeeId);

        if ($employee === null || blank($employee->email)) {
            return;
        }

        $mail = new AppointmentRescheduledMail(
            recipientName: (string) $employee->name,
            serviceName: 'Servicio',
            oldDate: Carbon::instance($event->oldScheduledAt)->locale(app()->getLocale())->isoFormat('dddd D [de] MMMM, YYYY'),
            oldTime: $event->oldScheduledAt->format('H:i'),
            newDate: Carbon::instance($event->newScheduledAt)->locale(app()->getLocale())->isoFormat('dddd D [de] MMMM, YYYY'),
            newTime: $event->newScheduledAt->format('H:i'),
        );

        Mail::to($employee->email)->send($mail);
    }
}
