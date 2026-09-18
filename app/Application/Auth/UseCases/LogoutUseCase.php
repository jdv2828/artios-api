<?php

declare(strict_types=1);

namespace App\Application\Auth\UseCases;

use App\Application\Auth\Ports\AuthRepositoryInterface;

final class LogoutUseCase
{
    public function __construct(
        private readonly AuthRepositoryInterface $authRepository,
    ) {
    }

    public function execute(int $tokenId): void
    {
        $this->authRepository->revokeToken($tokenId);
    }
}
