<?php

declare(strict_types=1);

namespace App\Application\Service\UseCases;

use App\Application\Service\Ports\ServiceRepositoryInterface;

final class ListServicesUseCase
{
    public function __construct(
        private readonly ServiceRepositoryInterface $repository,
    ) {
    }

    public function execute(): array
    {
        return $this->repository->findAll();
    }
}
