<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers\Auth;

use App\Application\Auth\Exceptions\InvalidCredentialsException;
use App\Application\Auth\UseCases\LoginUseCase;
use App\Infrastructure\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\JsonResponse;

final class LoginController
{
    public function __construct(
        private readonly LoginUseCase $useCase,
    ) {
    }

    public function __invoke(LoginRequest $request): JsonResponse
    {
        try {
            $response = $this->useCase->execute($request->toDto());
        } catch (InvalidCredentialsException) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        return response()->json([
            'user' => [
                'id' => $response->userId,
                'name' => $response->name,
                'email' => $response->email,
                'role' => $response->role,
            ],
            'token_type' => $response->tokenType,
            'access_token' => $response->accessToken,
        ]);
    }
}
