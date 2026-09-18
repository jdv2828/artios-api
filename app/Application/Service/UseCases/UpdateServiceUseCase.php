<?php

declare(strict_types=1);

namespace App\Application\Service\UseCases;

use App\Application\Service\DTO\UpdateServiceDTO;
use App\Application\Service\Exceptions\ServiceNotFoundException;
use App\Application\Service\Ports\ServiceRepositoryInterface;
use App\Domain\Service\Service;

final class UpdateServiceUseCase
{
    public function __construct(
        private readonly ServiceRepositoryInterface $repository,
    ) {
    }

    public function execute(UpdateServiceDTO $dto): Service
    {
        $service = $this->repository->findById($dto->id);

        if ($service === null) {
            throw new ServiceNotFoundException('Service not found.');
        }

        $service->rename($dto->name);
        $service->changeDuration($dto->durationMinutes);
        $service->changePrice($dto->price);

        if ($dto->active) {
            $service->activate();
        } else {
            $service->deactivate();
        }

        return $this->repository->save($service);
    }
}
