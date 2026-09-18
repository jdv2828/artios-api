<?php

declare(strict_types=1);

namespace App\Application\Appointment\UseCases;

use App\Application\Appointment\Exceptions\AppointmentNotFoundException;
use App\Application\Appointment\Ports\AppointmentRepositoryInterface;
use App\Domain\Appointment\Appointment;

final class GetAppointmentUseCase
{
    public function __construct(
        private readonly AppointmentRepositoryInterface $repository,
    ) {
    }

    public function execute(int $id): Appointment
    {
        $appointment = $this->repository->findById($id);

        if ($appointment === null) {
            throw new AppointmentNotFoundException('Appointment not found.');
        }

        return $appointment;
    }
}
