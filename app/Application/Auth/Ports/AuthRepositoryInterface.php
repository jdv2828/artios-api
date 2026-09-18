<?php

declare(strict_types=1);

namespace App\Application\Auth\Ports;

use App\Domain\User\Employee;

interface AuthRepositoryInterface
{
    public function authenticate(string $email, string $password): ?Employee;

    public function createToken(Employee $employee, string $name = 'auth'): string;

    public function revokeToken(int $tokenId): void;
}
