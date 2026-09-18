<?php

declare(strict_types=1);

namespace App\Application\Availability\UseCases;

use App\Application\Availability\Ports\AvailabilityRepositoryInterface;
use DateTimeImmutable;
use InvalidArgumentException;

final class ListAvailableSlotsUseCase
{
    public function __construct(
        private readonly AvailabilityRepositoryInterface $repository,
    ) {
    }

    /**
     * @return array<int, array{time: string, available: bool}>
     */
    public function execute(int $serviceId, string $date): array
    {
        $date = trim($date);
        $dt = DateTimeImmutable::createFromFormat('Y-m-d', $date);
        if ($dt === false) {
            throw new InvalidArgumentException('Invalid date format. Expected Y-m-d.');
        }

        return $this->repository->listAvailableSlotsForServiceOnDate($serviceId, $dt);
    }
}
