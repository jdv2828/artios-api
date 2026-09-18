<?php

declare(strict_types=1);

namespace App\Application\Appointment\UseCases;

use App\Application\Client\Exceptions\ClientNotFoundException;
use App\Application\Appointment\Ports\AppointmentRepositoryInterface;
use App\Application\Appointment\Ports\ClientRepositoryInterface;
use App\Domain\Appointment\Appointment;

final class ListClientAppointmentsUseCase
{
    public function __construct(
        private readonly ClientRepositoryInterface $clientRepository,
        private readonly AppointmentRepositoryInterface $appointmentRepository,
    ) {
    }

    /**
     * @return Appointment[]
     */
    public function execute(string $dni): array
    {
        $client = $this->clientRepository->findByDni($dni);

        if ($client === null) {
            throw new ClientNotFoundException('Client not found.');
        }

        return $this->appointmentRepository->findByClientId($client->id() ?? 0);
    }
}
