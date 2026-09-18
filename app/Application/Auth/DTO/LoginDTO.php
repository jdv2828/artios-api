<?php

declare(strict_types=1);

namespace App\Application\Auth\DTO;

use InvalidArgumentException;

final readonly class LoginDTO
{
    public function __construct(
        public string $email,
        public string $password,
    ) {
        if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Email is invalid.');
        }

        if (trim($this->password) === '') {
            throw new InvalidArgumentException('Password cannot be empty.');
        }
    }
}
