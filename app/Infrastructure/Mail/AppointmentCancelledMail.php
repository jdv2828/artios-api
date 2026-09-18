<?php

declare(strict_types=1);

namespace App\Infrastructure\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class AppointmentCancelledMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly string $recipientName,
        public readonly string $serviceName,
        public readonly string $scheduledDate,
        public readonly string $scheduledTime,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tu turno fue cancelado',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.appointment-cancelled',
        );
    }
}
