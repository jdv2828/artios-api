<?php

declare(strict_types=1);

namespace App\Application\Appointment\DTO;

use App\Domain\Appointment\AppointmentStatus;
use InvalidArgumentException;

final readonly class ChangeAppointmentStatusDTO
{
    public function __construct(
        public int $appointmentId,
        public AppointmentStatus $status,
    ) {
        if ($this->appointmentId <= 0) {
            throw new InvalidArgumentException('appointmentId must be greater than zero.');
        }
    }
}
