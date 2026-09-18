<?php

declare(strict_types=1);

namespace App\Application\Appointment\Exceptions;

use RuntimeException;

final class SlotUnavailableException extends RuntimeException
{
    public static function forDateTime(string $scheduledAt): self
    {
        return new self("The selected slot at {$scheduledAt} is not available.");
    }
}
