<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Service;

use App\Application\Service\DTO\UpdateServiceDTO;
use App\Application\Service\Exceptions\ServiceNotFoundException;
use App\Application\Service\Ports\ServiceRepositoryInterface;
use App\Application\Service\UseCases\UpdateServiceUseCase;
use App\Domain\Service\Service;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class UpdateServiceUseCaseTest extends TestCase
{
    private ServiceRepositoryInterface&MockObject $repository;
    private UpdateServiceUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(ServiceRepositoryInterface::class);
        $this->useCase = new UpdateServiceUseCase($this->repository);
    }

    public function test_it_updates_a_service(): void
    {
        $existing = new Service(1, 'Corte', 30, '1200.00', true);

        $this->repository
            ->expects(self::once())
            ->method('findById')
            ->with(1)
            ->willReturn($existing);

        $this->repository
            ->expects(self::once())
            ->method('save')
            ->willReturnCallback(fn (Service $service): Service => $service);

        $service = $this->useCase->execute(new UpdateServiceDTO(1, 'Corte premium', 45, 1800, false));

        self::assertSame('Corte premium', $service->name());
        self::assertSame(45, $service->durationMinutes());
        self::assertSame('1800.00', $service->price());
        self::assertFalse($service->active());
    }

    public function test_it_throws_when_service_does_not_exist(): void
    {
        $this->repository
            ->expects(self::once())
            ->method('findById')
            ->with(99)
            ->willReturn(null);

        $this->expectException(ServiceNotFoundException::class);

        $this->useCase->execute(new UpdateServiceDTO(99, 'Corte', 30, 1200, true));
    }
}
