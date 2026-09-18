<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Adapters;

use App\Application\Auth\Ports\AuthRepositoryInterface;
use App\Domain\User\Employee;
use App\Domain\User\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

final class EloquentAuthRepository implements AuthRepositoryInterface
{
    public function authenticate(string $email, string $password): ?Employee
    {
        $user = User::query()->where('email', $email)->first();

        if ($user === null || ! Hash::check($password, (string) $user->password)) {
            return null;
        }

        return new Employee(
            id: (int) $user->id,
            name: (string) $user->name,
            email: (string) $user->email,
            role: $user->role instanceof UserRole ? $user->role : UserRole::from((string) $user->role),
        );
    }

    public function createToken(Employee $employee, string $name = 'auth'): string
    {
        $user = User::query()->findOrFail($employee->id());

        return $user->createToken($name)->plainTextToken;
    }

    public function revokeToken(int $tokenId): void
    {
        $token = PersonalAccessToken::query()->find($tokenId);

        if ($token !== null) {
            $token->delete();
        }
    }
}
