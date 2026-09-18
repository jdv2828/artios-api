<?php

declare(strict_types=1);

namespace App\Infrastructure\Listeners;

use App\Infrastructure\Events\LaravelAppointmentRescheduled;
use App\Infrastructure\Mail\AppointmentRescheduledMail;
use App\Infrastructure\Persistence\Eloquent\Models\AppointmentModel;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

final class SendRescheduleNotificationEmail
{
    public function handle(LaravelAppointmentRescheduled $event): void
    {
        $appointment = AppointmentModel::query()
            ->with(['client', 'service'])
            ->find($event->appointmentId);

        if (
            $appointment === null
            || $appointment->client === null
            || $appointment->client->email === null
        ) {
            return;
        }

        $mail = new AppointmentRescheduledMail(
            recipientName: trim($appointment->client->first_name.' '.$appointment->client->last_name),
            serviceName: (string) ($appointment->service?->name ?? 'Servicio'),
            oldDate: Carbon::instance($event->oldScheduledAt)->locale(app()->getLocale())->isoFormat('dddd D [de] MMMM, YYYY'),
            oldTime: $event->oldScheduledAt->format('H:i'),
            newDate: Carbon::instance($event->newScheduledAt)->locale(app()->getLocale())->isoFormat('dddd D [de] MMMM, YYYY'),
            newTime: $event->newScheduledAt->format('H:i'),
        );

        Mail::to($appointment->client->email)->send($mail);
    }
}
