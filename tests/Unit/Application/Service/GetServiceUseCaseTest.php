<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Service;

use App\Application\Service\Exceptions\ServiceNotFoundException;
use App\Application\Service\Ports\ServiceRepositoryInterface;
use App\Application\Service\UseCases\GetServiceUseCase;
use App\Domain\Service\Service;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class GetServiceUseCaseTest extends TestCase
{
    private ServiceRepositoryInterface&MockObject $repository;
    private GetServiceUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(ServiceRepositoryInterface::class);
        $this->useCase = new GetServiceUseCase($this->repository);
    }

    public function test_it_returns_a_service_by_id(): void
    {
        $this->repository
            ->expects(self::once())
            ->method('findById')
            ->with(1)
            ->willReturn(new Service(1, 'Corte', 30, '1200.00', true));

        $service = $this->useCase->execute(1);

        self::assertSame('Corte', $service->name());
    }

    public function test_it_throws_when_service_is_missing(): void
    {
        $this->repository
            ->expects(self::once())
            ->method('findById')
            ->with(99)
            ->willReturn(null);

        $this->expectException(ServiceNotFoundException::class);

        $this->useCase->execute(99);
    }
}
