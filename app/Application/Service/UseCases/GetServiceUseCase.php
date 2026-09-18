<?php

declare(strict_types=1);

namespace App\Application\Service\UseCases;

use App\Application\Service\Exceptions\ServiceNotFoundException;
use App\Application\Service\Ports\ServiceRepositoryInterface;
use App\Domain\Service\Service;

final class GetServiceUseCase
{
    public function __construct(
        private readonly ServiceRepositoryInterface $repository,
    ) {
    }

    public function execute(int $id): Service
    {
        $service = $this->repository->findById($id);

        if ($service === null) {
            throw new ServiceNotFoundException('Service not found.');
        }

        return $service;
    }
}
