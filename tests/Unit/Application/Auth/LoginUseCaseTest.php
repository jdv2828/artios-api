<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Auth;

use App\Application\Auth\DTO\LoginDTO;
use App\Application\Auth\Exceptions\InvalidCredentialsException;
use App\Application\Auth\Ports\AuthRepositoryInterface;
use App\Application\Auth\UseCases\LoginUseCase;
use App\Domain\User\Employee;
use App\Domain\User\UserRole;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class LoginUseCaseTest extends TestCase
{
    private AuthRepositoryInterface&MockObject $authRepository;
    private LoginUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authRepository = $this->createMock(AuthRepositoryInterface::class);
        $this->useCase = new LoginUseCase($this->authRepository);
    }

    public function test_it_returns_a_token_when_credentials_are_valid(): void
    {
        $employee = new Employee(1, 'Admin', 'admin@turnero.com', UserRole::Admin);

        $this->authRepository
            ->expects(self::once())
            ->method('authenticate')
            ->with('admin@turnero.com', 'secret')
            ->willReturn($employee);

        $this->authRepository
            ->expects(self::once())
            ->method('createToken')
            ->with($employee)
            ->willReturn('plain-token');

        $response = $this->useCase->execute(new LoginDTO('admin@turnero.com', 'secret'));

        self::assertSame('plain-token', $response->accessToken);
        self::assertSame('admin@turnero.com', $response->email);
        self::assertSame('admin', $response->role);
    }

    public function test_it_throws_when_credentials_are_invalid(): void
    {
        $this->authRepository
            ->expects(self::once())
            ->method('authenticate')
            ->willReturn(null);

        $this->expectException(InvalidCredentialsException::class);

        $this->useCase->execute(new LoginDTO('admin@turnero.com', 'wrong'));
    }
}
