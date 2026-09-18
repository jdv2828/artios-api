<?php

declare(strict_types=1);

namespace App\Application\Appointment\DTO;

final readonly class AppointmentSearchResultDTO
{
    /**
     * @param array<int, mixed> $items
     */
    public function __construct(
        public array $items,
        public int $total,
        public int $page,
        public int $perPage,
        public int $lastPage,
    ) {
    }
}
