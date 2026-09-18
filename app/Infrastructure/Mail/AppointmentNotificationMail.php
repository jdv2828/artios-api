<?php

declare(strict_types=1);

namespace App\Infrastructure\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class AppointmentNotificationMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly string $kind,
        public readonly string $recipientName,
        public readonly string $serviceName,
        public readonly string $scheduledDate,
        public readonly string $scheduledTime,
        public readonly string $confirmationUrl,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->kind === 'confirmation'
                ? 'Confirmá tu turno'
                : 'Recordatorio de tu turno',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.appointment-notification',
        );
    }
}
