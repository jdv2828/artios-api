<?php

declare(strict_types=1);

namespace App\Infrastructure\Listeners;

use App\Infrastructure\Events\LaravelAppointmentCancelled;
use App\Infrastructure\Mail\AppointmentCancelledMail;
use App\Infrastructure\Persistence\Eloquent\Models\AppointmentModel;
use Illuminate\Support\Facades\Mail;

final class SendAppointmentCancelledEmail
{
    public function handle(LaravelAppointmentCancelled $event): void
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

        $mail = new AppointmentCancelledMail(
            recipientName: trim($appointment->client->first_name.' '.$appointment->client->last_name),
            serviceName: (string) ($appointment->service?->name ?? 'Servicio'),
            scheduledDate: $appointment->scheduled_at->locale(app()->getLocale())->isoFormat('dddd D [de] MMMM, YYYY'),
            scheduledTime: $appointment->scheduled_at->format('H:i'),
        );

        Mail::to($appointment->client->email)->send($mail);
    }
}
