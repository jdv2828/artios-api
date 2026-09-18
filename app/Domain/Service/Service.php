<?php

declare(strict_types=1);

namespace App\Domain\Service;

use InvalidArgumentException;

final class Service
{
    public function __construct(
        private ?int $id,
        private string $name,
        private int $durationMinutes,
        private string $price,
        private bool $active = true,
    ) {
        $this->rename($name);
        $this->changeDuration($durationMinutes);
        $this->changePrice($price);

        if ($this->id !== null && $this->id <= 0) {
            throw new InvalidArgumentException('Service id must be greater than zero.');
        }
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function durationMinutes(): int
    {
        return $this->durationMinutes;
    }

    public function price(): string
    {
        return $this->price;
    }

    public function active(): bool
    {
        return $this->active;
    }

    public function assignId(int $id): void
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('Service id must be greater than zero.');
        }

        $this->id = $id;
    }

    public function rename(string $name): void
    {
        $name = trim($name);

        if ($name === '') {
            throw new InvalidArgumentException('Service name cannot be empty.');
        }

        $this->name = $name;
    }

    public function changeDuration(int $durationMinutes): void
    {
        if ($durationMinutes <= 0) {
            throw new InvalidArgumentException('Service duration must be greater than zero.');
        }

        $this->durationMinutes = $durationMinutes;
    }

    public function changePrice(string|int|float $price): void
    {
        if (! is_numeric($price) || (float) $price < 0) {
            throw new InvalidArgumentException('Service price must be a non-negative number.');
        }

        $this->price = number_format((float) $price, 2, '.', '');
    }

    public function activate(): void
    {
        $this->active = true;
    }

    public function deactivate(): void
    {
        $this->active = false;
    }
}
