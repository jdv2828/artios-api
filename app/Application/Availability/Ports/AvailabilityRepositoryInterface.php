<?php

declare(strict_types=1);

namespace App\Application\Availability\Ports;

use DateTimeImmutable;

interface AvailabilityRepositoryInterface
{
    /**
     * @return array<int, array{time: string, available: bool}>
     */
    public function listAvailableSlotsForServiceOnDate(int $serviceId, DateTimeImmutable $date): array;

    public function findAvailableEmployeeIdForServiceAt(int $serviceId, DateTimeImmutable $scheduledAt): ?int;
}
