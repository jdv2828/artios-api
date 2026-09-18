<?php

declare(strict_types=1);

namespace App\Application\Service\DTO;

use InvalidArgumentException;

final readonly class CreateServiceDTO
{
    public function __construct(
        public string $name,
        public int $durationMinutes,
        public string|int|float $price,
    ) {
        if (trim($this->name) === '') {
            throw new InvalidArgumentException('name cannot be empty.');
        }

        if ($this->durationMinutes <= 0) {
            throw new InvalidArgumentException('durationMinutes must be greater than zero.');
        }

        if (! is_numeric($this->price) || (float) $this->price < 0) {
            throw new InvalidArgumentException('price must be a non-negative number.');
        }
    }
}
