<?php

declare(strict_types=1);

namespace App\Application\Appointment\UseCases;

use App\Application\Appointment\DTO\RescheduleAppointmentDTO;
use App\Application\Appointment\Exceptions\AppointmentNotFoundException;
use App\Application\Appointment\Exceptions\SlotUnavailableException;
use App\Application\Appointment\Ports\AppointmentRepositoryInterface;
use App\Application\Availability\Ports\AvailabilityRepositoryInterface;
use App\Domain\Appointment\Appointment;
use App\Domain\Appointment\AppointmentStatus;
use InvalidArgumentException;

final class RescheduleAppointmentUseCase
{
    public function __construct(
        private readonly AppointmentRepositoryInterface $appointmentRepository,
        private readonly AvailabilityRepositoryInterface $availabilityRepository,
    ) {
    }

    public function execute(RescheduleAppointmentDTO $dto): Appointment
    {
        $appointment = $this->appointmentRepository->findById($dto->appointmentId);

        if ($appointment === null) {
            throw new AppointmentNotFoundException('Appointment not found.');
        }

        if (! in_array($appointment->status(), [AppointmentStatus::Scheduled, AppointmentStatus::Confirmed], true)) {
            throw new InvalidArgumentException('Only scheduled or confirmed appointments can be rescheduled.');
        }

        $availableEmployeeId = $this->availabilityRepository->findAvailableEmployeeIdForServiceAt(
            $appointment->serviceId(),
            $dto->scheduledAt,
        );

        if ($availableEmployeeId === null || $availableEmployeeId !== $dto->employeeId) {
            throw SlotUnavailableException::forDateTime($dto->scheduledAt->format('Y-m-d H:i'));
        }

        $appointment->reschedule($dto->scheduledAt, $dto->employeeId);

        return $this->appointmentRepository->save($appointment);
    }
}
