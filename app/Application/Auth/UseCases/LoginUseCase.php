<?php

declare(strict_types=1);

namespace App\Application\Auth\UseCases;

use App\Application\Auth\DTO\LoginDTO;
use App\Application\Auth\DTO\LoginResponseDTO;
use App\Application\Auth\Exceptions\InvalidCredentialsException;
use App\Application\Auth\Ports\AuthRepositoryInterface;

final class LoginUseCase
{
    public function __construct(
        private readonly AuthRepositoryInterface $authRepository,
    ) {
    }

    public function execute(LoginDTO $dto): LoginResponseDTO
    {
        $employee = $this->authRepository->authenticate($dto->email, $dto->password);

        if ($employee === null) {
            throw new InvalidCredentialsException('The provided credentials are invalid.');
        }

        $accessToken = $this->authRepository->createToken($employee);

        return LoginResponseDTO::fromEmployee($employee, $accessToken);
    }
}
