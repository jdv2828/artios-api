<?php

declare(strict_types=1);

namespace App\Application\Appointment\Ports;

use App\Application\Appointment\DTO\AppointmentSearchResultDTO;
use App\Application\Appointment\DTO\ListAppointmentsDTO;
use App\Domain\Appointment\Appointment;

interface AppointmentRepositoryInterface
{
    public function findById(int $id): ?Appointment;

    public function findByConfirmationToken(string $token): ?Appointment;

    /**
     * @return Appointment[]
     */
    public function findByClientId(int $clientId): array;

    public function findAllFiltered(ListAppointmentsDTO $criteria): AppointmentSearchResultDTO;

    public function save(Appointment $appointment): Appointment;
}
