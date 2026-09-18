<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Appointment;

use App\Application\Appointment\DTO\ChangeAppointmentStatusDTO;
use App\Application\Appointment\Exceptions\AppointmentNotFoundException;
use App\Application\Appointment\Ports\AppointmentRepositoryInterface;
use App\Application\Appointment\UseCases\ChangeAppointmentStatusUseCase;
use App\Domain\Appointment\Appointment;
use App\Domain\Appointment\AppointmentStatus;
use App\Domain\Appointment\ReminderPreferences;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ChangeAppointmentStatusUseCaseTest extends TestCase
{
    private AppointmentRepositoryInterface&MockObject $repository;
    private ChangeAppointmentStatusUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(AppointmentRepositoryInterface::class);
        $this->useCase = new ChangeAppointmentStatusUseCase($this->repository);
    }

    public function test_it_confirms_an_appointment(): void
    {
        $appointment = Appointment::schedule(1, 1, 1, new DateTimeImmutable('+1 day'), new ReminderPreferences(true, false));
        $appointment->assignId(10);

        $this->repository
            ->expects(self::once())
            ->method('findById')
            ->with(10)
            ->willReturn($appointment);

        $this->repository
            ->expects(self::once())
            ->method('save')
            ->with(self::callback(fn (Appointment $saved): bool => $saved->status() === AppointmentStatus::Confirmed))
            ->willReturnCallback(fn (Appointment $saved): Appointment => $saved);

        $updated = $this->useCase->execute(new ChangeAppointmentStatusDTO(10, AppointmentStatus::Confirmed));

        self::assertSame(AppointmentStatus::Confirmed, $updated->status());
    }

    public function test_it_rejects_invalid_transition(): void
    {
        $appointment = Appointment::schedule(1, 1, 1, new DateTimeImmutable('+1 day'), new ReminderPreferences(true, false));
        $appointment->assignId(10);

        $this->repository
            ->expects(self::once())
            ->method('findById')
            ->willReturn($appointment);

        $this->expectException(InvalidArgumentException::class);

        $this->useCase->execute(new ChangeAppointmentStatusDTO(10, AppointmentStatus::Completed));
    }

    public function test_it_throws_when_appointment_is_missing(): void
    {
        $this->repository
            ->expects(self::once())
            ->method('findById')
            ->with(99)
            ->willReturn(null);

        $this->expectException(AppointmentNotFoundException::class);

        $this->useCase->execute(new ChangeAppointmentStatusDTO(99, AppointmentStatus::Confirmed));
    }
}
