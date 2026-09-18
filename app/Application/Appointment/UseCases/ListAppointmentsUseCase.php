<?php

declare(strict_types=1);

namespace App\Application\Appointment\UseCases;

use App\Application\Appointment\DTO\AppointmentSearchResultDTO;
use App\Application\Appointment\DTO\ListAppointmentsDTO;
use App\Application\Appointment\Ports\AppointmentRepositoryInterface;

final class ListAppointmentsUseCase
{
    public function __construct(
        private readonly AppointmentRepositoryInterface $repository,
    ) {
    }

    public function execute(ListAppointmentsDTO $criteria): AppointmentSearchResultDTO
    {
        return $this->repository->findAllFiltered($criteria);
    }
}
