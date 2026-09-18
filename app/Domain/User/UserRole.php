<?php

declare(strict_types=1);

namespace App\Domain\User;

enum UserRole: string
{
    case Employee = 'employee';
    case Admin = 'admin';

    public function isBackOffice(): bool
    {
        return true;
    }
}
