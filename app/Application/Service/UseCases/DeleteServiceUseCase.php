<?php

declare(strict_types=1);

namespace App\Application\Service\UseCases;

use App\Application\Service\Exceptions\ServiceNotFoundException;
use App\Application\Service\Ports\ServiceRepositoryInterface;

final class DeleteServiceUseCase
{
    public function __construct(
        private readonly ServiceRepositoryInterface $repository,
    ) {
    }

    public function execute(int $id): void
    {
        if ($id <= 0) {
            throw new ServiceNotFoundException('Service not found.');
        }

        if ($this->repository->findById($id) === null) {
            throw new ServiceNotFoundException('Service not found.');
        }

        $this->repository->delete($id);
    }
}
