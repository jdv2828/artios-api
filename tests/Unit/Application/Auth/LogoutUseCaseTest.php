<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Auth;

use App\Application\Auth\Ports\AuthRepositoryInterface;
use App\Application\Auth\UseCases\LogoutUseCase;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class LogoutUseCaseTest extends TestCase
{
    private AuthRepositoryInterface&MockObject $authRepository;
    private LogoutUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authRepository = $this->createMock(AuthRepositoryInterface::class);
        $this->useCase = new LogoutUseCase($this->authRepository);
    }

    public function test_it_revokes_the_token(): void
    {
        $this->authRepository
            ->expects(self::once())
            ->method('revokeToken')
            ->with(99);

        $this->useCase->execute(99);
    }
}
