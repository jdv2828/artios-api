<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Adapters;

use App\Application\Appointment\Ports\ClientRepositoryInterface;
use App\Domain\Client\Client;
use App\Infrastructure\Persistence\Eloquent\Models\ClientModel;

final class EloquentClientRepository implements ClientRepositoryInterface
{
    public function findByDni(string $dni): ?Client
    {
        $model = ClientModel::query()->where('dni', $dni)->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function save(Client $client): Client
    {
        $model = ClientModel::query()->updateOrCreate(
            ['dni' => $client->dni()],
            [
                'first_name' => $client->firstName(),
                'last_name' => $client->lastName(),
                'phone' => $client->phone(),
                'email' => $client->email(),
            ],
        );

        $client->assignId((int) $model->id);

        return $client;
    }

    private function toDomain(ClientModel $model): Client
    {
        return new Client(
            id: (int) $model->id,
            firstName: (string) $model->first_name,
            lastName: (string) $model->last_name,
            dni: (string) $model->dni,
            phone: (string) $model->phone,
            email: $model->email !== null ? (string) $model->email : null,
        );
    }
}
