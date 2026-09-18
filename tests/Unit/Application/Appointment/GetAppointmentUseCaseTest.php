<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Appointment;

use App\Application\Appointment\Exceptions\AppointmentNotFoundException;
use App\Application\Appointment\Ports\AppointmentRepositoryInterface;
use App\Application\Appointment\UseCases\GetAppointmentUseCase;
use App\Domain\Appointment\Appointment;
use App\Domain\Appointment\ReminderPreferences;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class GetAppointmentUseCaseTest extends TestCase
{
    private AppointmentRepositoryInterface&MockObject $repository;
    private GetAppointmentUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(AppointmentRepositoryInterface::class);
        $this->useCase = new GetAppointmentUseCase($this->repository);
    }

    public function test_it_returns_an_appointment(): void
    {
        $appointment = Appointment::schedule(1, 1, 1, new DateTimeImmutable('+1 day'), new ReminderPreferences(true, false));
        $appointment->assignId(10);

        $this->repository
            ->expects(self::once())
            ->method('findById')
            ->with(10)
            ->willReturn($appointment);

        self::assertSame(10, $this->useCase->execute(10)->id());
    }

    public function test_it_throws_when_missing(): void
    {
        $this->repository
            ->expects(self::once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $this->expectException(AppointmentNotFoundException::class);

        $this->useCase->execute(999);
    }
}
