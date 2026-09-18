<?php

declare(strict_types=1);

namespace App\Application\Auth\DTO;

use App\Domain\User\Employee;

final readonly class LoginResponseDTO
{
    public function __construct(
        public int $userId,
        public string $name,
        public string $email,
        public string $role,
        public string $accessToken,
        public string $tokenType = 'Bearer',
    ) {
    }

    public static function fromEmployee(Employee $employee, string $accessToken): self
    {
        return new self(
            userId: $employee->id(),
            name: $employee->name(),
            email: $employee->email(),
            role: $employee->role()->value,
            accessToken: $accessToken,
        );
    }
}
