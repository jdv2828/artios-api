<?php

declare(strict_types=1);

namespace App\Application\Service\Ports;

use App\Domain\Service\Service;

interface ServiceRepositoryInterface
{
    /**
     * @return Service[]
     */
    public function findAll(): array;

    /**
     * @return Service[]
     */
    public function findActive(): array;

    public function findById(int $id): ?Service;

    public function save(Service $service): Service;

    public function delete(int $id): void;
}
