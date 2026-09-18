<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Service;

use App\Application\Service\Exceptions\ServiceNotFoundException;
use App\Application\Service\Ports\ServiceRepositoryInterface;
use App\Application\Service\UseCases\DeleteServiceUseCase;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class DeleteServiceUseCaseTest extends TestCase
{
    private ServiceRepositoryInterface&MockObject $repository;
    private DeleteServiceUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(ServiceRepositoryInterface::class);
        $this->useCase = new DeleteServiceUseCase($this->repository);
    }

    public function test_it_deletes_a_service(): void
    {
        $this->repository
            ->expects(self::once())
            ->method('findById')
            ->with(1)
            ->willReturn(new \App\Domain\Service\Service(1, 'Corte', 30, '1200.00', true));

        $this->repository
            ->expects(self::once())
            ->method('delete')
            ->with(1);

        $this->useCase->execute(1);
    }

    public function test_it_throws_when_service_does_not_exist(): void
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
