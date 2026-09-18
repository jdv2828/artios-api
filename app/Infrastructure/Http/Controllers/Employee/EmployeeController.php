<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers\Employee;

use App\Models\User;
use Illuminate\Http\JsonResponse;

final class EmployeeController
{
    public function index(): JsonResponse
    {
        $employees = User::query()
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role']);

        return response()->json([
            'data' => $employees->map(fn (User $user): array => [
                'id' => (int) $user->id,
                'name' => (string) $user->name,
                'email' => (string) $user->email,
                'role' => (string) $user->role->value,
            ])->all(),
        ]);
    }
}
