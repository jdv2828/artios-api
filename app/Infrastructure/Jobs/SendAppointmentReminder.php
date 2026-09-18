<?php

declare(strict_types=1);

namespace App\Infrastructure\Jobs;

use App\Infrastructure\Mail\AppointmentNotificationMail;
use App\Infrastructure\Persistence\Eloquent\Models\AppointmentModel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

final class SendAppointmentReminder implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly int $appointmentId,
        public readonly string $reminderType,
    ) {
    }

    public function handle(): void
    {
        $appointment = AppointmentModel::query()
            ->with(['client', 'service'])
            ->find($this->appointmentId);

        if (
            $appointment === null
            || $appointment->client === null
            || $appointment->client->email === null
            || blank($appointment->confirmation_token)
        ) {
            return;
        }

        if (in_array((string) $appointment->status, ['cancelled', 'completed', 'no_show'], true)) {
            return;
        }

        $mail = new AppointmentNotificationMail(
            kind: 'reminder',
            recipientName: trim($appointment->client->first_name.' '.$appointment->client->last_name),
            serviceName: (string) ($appointment->service?->name ?? 'Servicio'),
            scheduledDate: $appointment->scheduled_at->locale(app()->getLocale())->isoFormat('dddd D [de] MMMM, YYYY'),
            scheduledTime: $appointment->scheduled_at->format('H:i'),
            confirmationUrl: rtrim((string) config('app.frontend_url'), '/').'/confirmacion?token='.(string) $appointment->confirmation_token,
        );

        Mail::to($appointment->client->email)->send($mail);
    }
}
