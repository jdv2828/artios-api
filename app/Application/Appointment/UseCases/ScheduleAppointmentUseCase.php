<?php

declare(strict_types=1);

namespace App\Application\Appointment\UseCases;

use App\Application\Appointment\DTO\ScheduleAppointmentDTO;
use App\Application\Appointment\Exceptions\NoEmployeeAvailableException;
use App\Application\Appointment\Ports\AppointmentRepositoryInterface;
use App\Application\Appointment\Ports\ClientRepositoryInterface;
use App\Application\Availability\Ports\AvailabilityRepositoryInterface;
use App\Domain\Appointment\Appointment;
use App\Domain\Appointment\ReminderPreferences;
use App\Domain\Client\Client;
use RuntimeException;

final class ScheduleAppointmentUseCase
{
    public function __construct(
        private readonly ClientRepositoryInterface $clientRepository,
        private readonly AppointmentRepositoryInterface $appointmentRepository,
        private readonly AvailabilityRepositoryInterface $availabilityRepository,
    ) {
    }

    public function execute(ScheduleAppointmentDTO $dto): Appointment
    {
        $client = $this->clientRepository->findByDni($dto->dni);

        if ($client === null) {
            $client = new Client(
                id: null,
                firstName: $dto->firstName,
                lastName: $dto->lastName,
                dni: $dto->dni,
                phone: $dto->phone,
                email: $dto->email,
            );
        } else {
            if ($client->phone() !== $dto->phone) {
                $client->updatePhone($dto->phone);
            }

            if ($client->email() !== $dto->email) {
                $client->updateEmail($dto->email);
            }
        }

        $client = $this->clientRepository->save($client);

        $clientId = $client->id();
        if ($clientId === null) {
            throw new RuntimeException('Client repository must return a persisted client with id.');
        }

        $employeeId = $this->availabilityRepository->findAvailableEmployeeIdForServiceAt($dto->serviceId, $dto->scheduledAt);
        if ($employeeId === null) {
            throw new NoEmployeeAvailableException('No hay profesionales disponibles para ese horario.');
        }

        $appointment = Appointment::schedule(
            clientId: $clientId,
            employeeId: $employeeId,
            serviceId: $dto->serviceId,
            scheduledAt: $dto->scheduledAt,
            reminderPreferences: new ReminderPreferences(
                remind1DayBefore: $dto->remind1DayBefore,
                remind30MinsBefore: $dto->remind30MinsBefore,
            ),
        );

        return $this->appointmentRepository->save($appointment);
    }
}
