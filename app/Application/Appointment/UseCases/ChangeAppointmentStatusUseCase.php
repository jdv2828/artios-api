<?php

declare(strict_types=1);

namespace App\Application\Appointment\UseCases;

use App\Application\Appointment\DTO\ChangeAppointmentStatusDTO;
use App\Application\Appointment\Exceptions\AppointmentNotFoundException;
use App\Application\Appointment\Ports\AppointmentRepositoryInterface;
use App\Domain\Appointment\Appointment;
use App\Domain\Appointment\AppointmentStatus;
use InvalidArgumentException;

final class ChangeAppointmentStatusUseCase
{
    public function __construct(
        private readonly AppointmentRepositoryInterface $repository,
    ) {
    }

    public function execute(ChangeAppointmentStatusDTO $dto): Appointment
    {
        $appointment = $this->repository->findById($dto->appointmentId);

        if ($appointment === null) {
            throw new AppointmentNotFoundException('Appointment not found.');
        }

        try {
            match ($dto->status) {
                AppointmentStatus::Confirmed => $appointment->confirm(),
                AppointmentStatus::Cancelled => $appointment->cancel(),
                AppointmentStatus::Completed => $appointment->complete(),
                AppointmentStatus::NoShow => $appointment->markNoShow(),
                default => throw new InvalidArgumentException('Unsupported appointment status transition.'),
            };
        } catch (InvalidArgumentException $exception) {
            throw $exception;
        }

        return $this->repository->save($appointment);
    }
}
