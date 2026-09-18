<?php

declare(strict_types=1);

namespace App\Domain\Appointment;

final class ReminderPreferences
{
    public function __construct(
        private readonly bool $remind1DayBefore,
        private readonly bool $remind30MinsBefore,
        private readonly bool $remind1HourBefore = true,
    ) {
    }

    public function remind1DayBefore(): bool
    {
        return $this->remind1DayBefore;
    }

    public function remind30MinsBefore(): bool
    {
        return $this->remind30MinsBefore;
    }

    public function remind1HourBefore(): bool
    {
        return $this->remind1HourBefore;
    }
}
