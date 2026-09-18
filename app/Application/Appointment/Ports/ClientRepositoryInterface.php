<?php

declare(strict_types=1);

namespace App\Application\Appointment\Ports;

use App\Domain\Client\Client;

interface ClientRepositoryInterface
{
    public function findByDni(string $dni): ?Client;

    public function save(Client $client): Client;
}
