<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers\Auth;

use App\Application\Auth\UseCases\LogoutUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class LogoutController
{
    public function __construct(
        private readonly LogoutUseCase $useCase,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $tokenId = $request->user()?->currentAccessToken()?->id;

        if ($tokenId !== null) {
            $this->useCase->execute((int) $tokenId);
        }

        return response()->json(['message' => 'Logged out successfully.']);
    }
}
