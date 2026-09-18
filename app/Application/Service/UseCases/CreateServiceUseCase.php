<?php

declare(strict_types=1);

namespace App\Application\Service\UseCases;

use App\Application\Service\DTO\CreateServiceDTO;
use App\Application\Service\Ports\ServiceRepositoryInterface;
use App\Domain\Service\Service;

final class CreateServiceUseCase
{
    public function __construct(
        private readonly ServiceRepositoryInterface $repository,
    ) {
    }

    public function execute(CreateServiceDTO $dto): Service
    {
        return $this->repository->save(new Service(
            id: null,
            name: $dto->name,
            durationMinutes: $dto->durationMinutes,
            price: (string) $dto->price,
            active: true,
        ));
    }
}
