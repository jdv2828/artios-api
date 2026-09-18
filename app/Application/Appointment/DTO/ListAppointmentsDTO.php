<?php

declare(strict_types=1);

namespace App\Application\Appointment\DTO;

use InvalidArgumentException;

final readonly class ListAppointmentsDTO
{
    public function __construct(
        public ?string $scheduledDate = null,
        public ?string $scheduledDateFrom = null,
        public ?string $scheduledDateTo = null,
        public ?string $status = null,
        public ?string $clientDni = null,
        public ?string $clientName = null,
        public ?int $employeeId = null,
        public int $page = 1,
        public int $perPage = 15,
    ) {
        if ($this->page <= 0) {
            throw new InvalidArgumentException('page must be greater than zero.');
        }

        if ($this->perPage <= 0) {
            throw new InvalidArgumentException('perPage must be greater than zero.');
        }
    }
}
