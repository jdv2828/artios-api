<?php

declare(strict_types=1);

namespace App\Infrastructure\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class AppointmentRescheduledMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly string $recipientName,
        public readonly string $serviceName,
        public readonly string $oldDate,
        public readonly string $oldTime,
        public readonly string $newDate,
        public readonly string $newTime,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tu turno fue reprogramado',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.appointment-rescheduled',
        );
    }
}
