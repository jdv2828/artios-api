<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Service;

use App\Application\Service\DTO\CreateServiceDTO;
use App\Application\Service\Ports\ServiceRepositoryInterface;
use App\Application\Service\UseCases\CreateServiceUseCase;
use App\Domain\Service\Service;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class CreateServiceUseCaseTest extends TestCase
{
    private ServiceRepositoryInterface&MockObject $repository;
    private CreateServiceUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(ServiceRepositoryInterface::class);
        $this->useCase = new CreateServiceUseCase($this->repository);
    }

    public function test_it_creates_a_service(): void
    {
        $this->repository
            ->expects(self::once())
            ->method('save')
            ->with(self::callback(function (Service $service): bool {
                self::assertSame('Corte', $service->name());
                self::assertSame(30, $service->durationMinutes());
                self::assertSame('1200.00', $service->price());
                self::assertTrue($service->active());

                return true;
            }))
            ->willReturnCallback(function (Service $service): Service {
                $service->assignId(1);

                return $service;
            });

        $service = $this->useCase->execute(new CreateServiceDTO('Corte', 30, 1200));

        self::assertSame(1, $service->id());
    }
}
