<?php

declare(strict_types=1);

namespace App\Application\Appointment\UseCases;

use App\Application\Appointment\Exceptions\AppointmentNotFoundException;
use App\Application\Appointment\Ports\AppointmentRepositoryInterface;
use App\Domain\Appointment\Appointment;
use App\Domain\Appointment\AppointmentStatus;

final class ConfirmAppointmentUseCase
{
    public function __construct(
        private readonly AppointmentRepositoryInterface $repository,
    ) {
    }

    public function execute(string $confirmationToken): Appointment
    {
        $appointment = $this->repository->findByConfirmationToken($confirmationToken);

        if ($appointment === null) {
            throw new AppointmentNotFoundException('Appointment not found.');
        }

        if ($appointment->status() !== AppointmentStatus::Confirmed) {
            $appointment->confirm();
            $appointment = $this->repository->save($appointment);
        }

        return $appointment;
    }
}
