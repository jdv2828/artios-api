<?php

declare(strict_types=1);

namespace App\Domain\User;

use InvalidArgumentException;

final class Employee
{
    public function __construct(
        private readonly int $id,
        private readonly string $name,
        private readonly string $email,
        private readonly UserRole $role,
    ) {
        if ($this->id <= 0) {
            throw new InvalidArgumentException('Employee id must be greater than zero.');
        }

        if (trim($this->name) === '') {
            throw new InvalidArgumentException('Employee name cannot be empty.');
        }

        if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Employee email is invalid.');
        }
    }

    public function id(): int
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function role(): UserRole
    {
        return $this->role;
    }
}
