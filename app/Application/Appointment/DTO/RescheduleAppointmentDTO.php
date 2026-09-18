<?php

declare(strict_types=1);

namespace App\Application\Appointment\DTO;

use DateTimeImmutable;
use InvalidArgumentException;

final readonly class RescheduleAppointmentDTO
{
    public function __construct(
        public int $appointmentId,
        public DateTimeImmutable $scheduledAt,
        public int $employeeId,
    ) {
        if ($this->appointmentId <= 0) {
            throw new InvalidArgumentException('appointmentId must be greater than zero.');
        }

        if ($this->employeeId <= 0) {
            throw new InvalidArgumentException('employeeId must be greater than zero.');
        }

        if ($this->scheduledAt <= new DateTimeImmutable()) {
            throw new InvalidArgumentException('scheduledAt must be in the future.');
        }
    }
}
