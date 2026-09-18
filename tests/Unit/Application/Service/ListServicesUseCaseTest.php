<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Service;

use App\Application\Service\Ports\ServiceRepositoryInterface;
use App\Application\Service\UseCases\ListServicesUseCase;
use App\Domain\Service\Service;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ListServicesUseCaseTest extends TestCase
{
    private ServiceRepositoryInterface&MockObject $repository;
    private ListServicesUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(ServiceRepositoryInterface::class);
        $this->useCase = new ListServicesUseCase($this->repository);
    }

    public function test_it_lists_services(): void
    {
        $this->repository
            ->expects(self::once())
            ->method('findAll')
            ->willReturn([
                new Service(1, 'Corte', 30, '1200.00', true),
            ]);

        $services = $this->useCase->execute();

        self::assertCount(1, $services);
        self::assertSame('Corte', $services[0]->name());
    }
}
