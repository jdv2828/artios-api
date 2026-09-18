<?php

declare(strict_types=1);

namespace App\Application\Appointment\DTO;

use DateTimeImmutable;
use InvalidArgumentException;

final readonly class ScheduleAppointmentDTO
{
    public function __construct(
        public string $firstName,
        public string $lastName,
        public string $dni,
        public string $phone,
        public string $email,
        public int $serviceId,
        public DateTimeImmutable $scheduledAt,
        public bool $remind1DayBefore,
        public bool $remind30MinsBefore,
    ) {
        if (trim($this->firstName) === '') {
            throw new InvalidArgumentException('firstName cannot be empty.');
        }

        if (trim($this->lastName) === '') {
            throw new InvalidArgumentException('lastName cannot be empty.');
        }

        if (!preg_match('/^\d{7,10}$/', preg_replace('/\s+/', '', trim($this->dni)) ?? '')) {
            throw new InvalidArgumentException('dni must contain 7 to 10 digits.');
        }

        if (!preg_match('/^\+?[0-9\-\s]{6,20}$/', trim($this->phone))) {
            throw new InvalidArgumentException('phone must be a valid number with 6 to 20 characters.');
        }

        if (! filter_var(trim($this->email), FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('email must be a valid email address.');
        }

        if ($this->serviceId <= 0) {
            throw new InvalidArgumentException('serviceId must be greater than zero.');
        }

        if ($this->scheduledAt <= new DateTimeImmutable()) {
            throw new InvalidArgumentException('scheduledAt must be in the future.');
        }
    }
}
