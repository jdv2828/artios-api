<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Appointment;

use App\Application\Appointment\DTO\RescheduleAppointmentDTO;
use App\Application\Appointment\Exceptions\AppointmentNotFoundException;
use App\Application\Appointment\Exceptions\SlotUnavailableException;
use App\Application\Appointment\Ports\AppointmentRepositoryInterface;
use App\Application\Appointment\UseCases\RescheduleAppointmentUseCase;
use App\Application\Availability\Ports\AvailabilityRepositoryInterface;
use App\Domain\Appointment\Appointment;
use App\Domain\Appointment\AppointmentStatus;
use App\Domain\Appointment\ReminderPreferences;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class RescheduleAppointmentUseCaseTest extends TestCase
{
    private AppointmentRepositoryInterface&MockObject $appointmentRepository;
    private AvailabilityRepositoryInterface&MockObject $availabilityRepository;
    private RescheduleAppointmentUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->appointmentRepository = $this->createMock(AppointmentRepositoryInterface::class);
        $this->availabilityRepository = $this->createMock(AvailabilityRepositoryInterface::class);
        $this->useCase = new RescheduleAppointmentUseCase(
            $this->appointmentRepository,
            $this->availabilityRepository,
        );
    }

    public function test_it_reschedules_a_scheduled_appointment(): void
    {
        $appointment = Appointment::schedule(1, 1, 1, new DateTimeImmutable('+1 day'), new ReminderPreferences(true, false));
        $appointment->assignId(10);

        $newScheduledAt = new DateTimeImmutable('+3 days');

        $this->appointmentRepository
            ->expects(self::once())
            ->method('findById')
            ->with(10)
            ->willReturn($appointment);

        $this->availabilityRepository
            ->expects(self::once())
            ->method('findAvailableEmployeeIdForServiceAt')
            ->with(1, $newScheduledAt)
            ->willReturn(2);

        $this->appointmentRepository
            ->expects(self::once())
            ->method('save')
            ->with(self::callback(function (Appointment $saved) use ($newScheduledAt): bool {
                self::assertSame(AppointmentStatus::Scheduled, $saved->status());
                self::assertSame($newScheduledAt, $saved->scheduledAt());
                self::assertSame(2, $saved->employeeId());

                return true;
            }))
            ->willReturnCallback(fn (Appointment $saved): Appointment => $saved);

        $updated = $this->useCase->execute(new RescheduleAppointmentDTO(10, $newScheduledAt, 2));

        self::assertSame(2, $updated->employeeId());
        self::assertSame($newScheduledAt, $updated->scheduledAt());
    }

    public function test_it_throws_when_appointment_is_missing(): void
    {
        $this->appointmentRepository
            ->expects(self::once())
            ->method('findById')
            ->with(99)
            ->willReturn(null);

        $this->expectException(AppointmentNotFoundException::class);

        $this->useCase->execute(new RescheduleAppointmentDTO(99, new DateTimeImmutable('+3 days'), 2));
    }

    public function test_it_throws_when_slot_is_unavailable(): void
    {
        $appointment = Appointment::schedule(1, 1, 1, new DateTimeImmutable('+1 day'), new ReminderPreferences(true, false));
        $appointment->assignId(10);

        $newScheduledAt = new DateTimeImmutable('+3 days');

        $this->appointmentRepository
            ->expects(self::once())
            ->method('findById')
            ->with(10)
            ->willReturn($appointment);

        $this->availabilityRepository
            ->expects(self::once())
            ->method('findAvailableEmployeeIdForServiceAt')
            ->with(1, $newScheduledAt)
            ->willReturn(null);

        $this->expectException(SlotUnavailableException::class);

        $this->useCase->execute(new RescheduleAppointmentDTO(10, $newScheduledAt, 2));
    }

    public function test_it_throws_when_available_employee_differs_from_requested(): void
    {
        $appointment = Appointment::schedule(1, 1, 1, new DateTimeImmutable('+1 day'), new ReminderPreferences(true, false));
        $appointment->assignId(10);

        $newScheduledAt = new DateTimeImmutable('+3 days');

        $this->appointmentRepository
            ->expects(self::once())
            ->method('findById')
            ->with(10)
            ->willReturn($appointment);

        $this->availabilityRepository
            ->expects(self::once())
            ->method('findAvailableEmployeeIdForServiceAt')
            ->with(1, $newScheduledAt)
            ->willReturn(3); // Different employee than requested (2)

        $this->expectException(SlotUnavailableException::class);

        $this->useCase->execute(new RescheduleAppointmentDTO(10, $newScheduledAt, 2));
    }

    public function test_it_throws_when_appointment_status_is_cancelled(): void
    {
        $appointment = Appointment::schedule(1, 1, 1, new DateTimeImmutable('+1 day'), new ReminderPreferences(true, false));
        $appointment->assignId(10);
        $appointment->cancel();

        $this->appointmentRepository
            ->expects(self::once())
            ->method('findById')
            ->with(10)
            ->willReturn($appointment);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Only scheduled or confirmed appointments can be rescheduled.');

        $this->useCase->execute(new RescheduleAppointmentDTO(10, new DateTimeImmutable('+3 days'), 2));
    }
}
