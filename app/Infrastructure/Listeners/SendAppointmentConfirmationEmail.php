<?php

declare(strict_types=1);

namespace App\Infrastructure\Listeners;

use App\Infrastructure\Events\LaravelAppointmentConfirmed;
use App\Infrastructure\Events\LaravelAppointmentScheduled;
use App\Infrastructure\Mail\AppointmentNotificationMail;
use App\Infrastructure\Persistence\Eloquent\Models\AppointmentModel;
use Illuminate\Support\Facades\Mail;

final class SendAppointmentConfirmationEmail
{
    public function handle(LaravelAppointmentScheduled|LaravelAppointmentConfirmed $event): void
    {
        $appointment = AppointmentModel::query()
            ->with(['client', 'service'])
            ->find($event->appointmentId);

        if (
            $appointment === null
            || $appointment->client === null
            || $appointment->client->email === null
            || blank($appointment->confirmation_token)
        ) {
            return;
        }

        $mail = new AppointmentNotificationMail(
            kind: 'confirmation',
            recipientName: trim($appointment->client->first_name.' '.$appointment->client->last_name),
            serviceName: (string) ($appointment->service?->name ?? 'Servicio'),
            scheduledDate: $appointment->scheduled_at->locale(app()->getLocale())->isoFormat('dddd D [de] MMMM, YYYY'),
            scheduledTime: $appointment->scheduled_at->format('H:i'),
            confirmationUrl: rtrim((string) config('app.frontend_url'), '/').'/confirmacion?token='.(string) $appointment->confirmation_token,
        );

        Mail::to($appointment->client->email)->send($mail);
    }
}
