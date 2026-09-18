<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Appointment;

use App\Application\Appointment\DTO\ScheduleAppointmentDTO;
use App\Application\Appointment\Ports\AppointmentRepositoryInterface;
use App\Application\Appointment\Ports\ClientRepositoryInterface;
use App\Application\Appointment\UseCases\ScheduleAppointmentUseCase;
use App\Application\Availability\Ports\AvailabilityRepositoryInterface;
use App\Domain\Appointment\Appointment;
use App\Domain\Appointment\AppointmentStatus;
use App\Domain\Client\Client;
use App\Domain\Shared\Events\AppointmentScheduled;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ScheduleAppointmentUseCaseTest extends TestCase
{
    private ClientRepositoryInterface&MockObject $clientRepository;
    private AppointmentRepositoryInterface&MockObject $appointmentRepository;
    private AvailabilityRepositoryInterface&MockObject $availabilityRepository;
    private ScheduleAppointmentUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clientRepository = $this->createMock(ClientRepositoryInterface::class);
        $this->appointmentRepository = $this->createMock(AppointmentRepositoryInterface::class);
        $this->availabilityRepository = $this->createMock(AvailabilityRepositoryInterface::class);

        $this->useCase = new ScheduleAppointmentUseCase(
            clientRepository: $this->clientRepository,
            appointmentRepository: $this->appointmentRepository,
            availabilityRepository: $this->availabilityRepository,
        );
    }

    public function test_it_creates_a_new_client_when_dni_does_not_exist(): void
    {
        $dto = $this->makeDto(phone: '+54 11 5555-8888');

        $this->clientRepository
            ->expects(self::once())
            ->method('findByDni')
            ->with('30123456')
            ->willReturn(null);

        $this->clientRepository
            ->expects(self::once())
            ->method('save')
            ->with(self::callback(function (Client $client): bool {
                self::assertNull($client->id());
                self::assertSame('Ana', $client->firstName());
                self::assertSame('Perez', $client->lastName());
                self::assertSame('30123456', $client->dni());
                self::assertSame('+54 11 5555-8888', $client->phone());
                self::assertSame('ana@example.com', $client->email());

                return true;
            }))
            ->willReturn(new Client(
                id: 100,
                firstName: 'Ana',
                lastName: 'Perez',
                dni: '30123456',
                phone: '+54 11 5555-8888',
                email: 'ana@example.com',
            ));

        $this->appointmentRepository
            ->expects(self::once())
            ->method('save')
            ->with(self::callback(function (Appointment $appointment): bool {
                self::assertSame(100, $appointment->clientId());
                self::assertSame(50, $appointment->employeeId());
                self::assertSame(10, $appointment->serviceId());
                self::assertSame(AppointmentStatus::Scheduled, $appointment->status());
                self::assertTrue($appointment->reminderPreferences()->remind1DayBefore());
                self::assertFalse($appointment->reminderPreferences()->remind30MinsBefore());
                self::assertTrue($appointment->reminderPreferences()->remind1HourBefore());

                return true;
            }))
            ->willReturnCallback(function (Appointment $appointment): Appointment {
                $appointment->assignId(500);

                return $appointment;
            });

        $this->availabilityRepository
            ->expects(self::once())
            ->method('findAvailableEmployeeIdForServiceAt')
            ->willReturn(50);

        $appointment = $this->useCase->execute($dto);

        self::assertSame(500, $appointment->id());
        $events = $appointment->pullDomainEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(AppointmentScheduled::class, $events[0]);
    }

    public function test_it_updates_phone_when_client_exists_and_phone_changed(): void
    {
        $dto = $this->makeDto(phone: '+54 11 4444-9999');

        $existingClient = new Client(
            id: 200,
            firstName: 'Ana',
            lastName: 'Perez',
            dni: '30123456',
            phone: '+54 11 5555-8888',
            email: 'ana@example.com',
        );

        $this->clientRepository
            ->expects(self::once())
            ->method('findByDni')
            ->with('30123456')
            ->willReturn($existingClient);

        $this->clientRepository
            ->expects(self::once())
            ->method('save')
            ->with(self::callback(function (Client $client): bool {
                self::assertSame(200, $client->id());
                self::assertSame('+54 11 4444-9999', $client->phone());

                return true;
            }))
            ->willReturn($existingClient);

        $this->appointmentRepository
            ->expects(self::once())
            ->method('save')
            ->willReturnCallback(function (Appointment $appointment): Appointment {
                $appointment->assignId(600);

                return $appointment;
            });

        $this->availabilityRepository
            ->expects(self::once())
            ->method('findAvailableEmployeeIdForServiceAt')
            ->willReturn(50);

        $appointment = $this->useCase->execute($dto);

        self::assertSame(600, $appointment->id());
        self::assertSame(200, $appointment->clientId());
        self::assertSame(AppointmentStatus::Scheduled, $appointment->status());
    }

    public function test_it_keeps_phone_when_client_exists_and_phone_is_the_same(): void
    {
        $dto = $this->makeDto(phone: '+54 11 5555-8888');

        $existingClient = new Client(
            id: 300,
            firstName: 'Ana',
            lastName: 'Perez',
            dni: '30123456',
            phone: '+54 11 5555-8888',
            email: 'ana@example.com',
        );

        $this->clientRepository
            ->expects(self::once())
            ->method('findByDni')
            ->with('30123456')
            ->willReturn($existingClient);

        $this->clientRepository
            ->expects(self::once())
            ->method('save')
            ->with(self::callback(function (Client $client): bool {
                self::assertSame(300, $client->id());
                self::assertSame('+54 11 5555-8888', $client->phone());

                return true;
            }))
            ->willReturn($existingClient);

        $this->appointmentRepository
            ->expects(self::once())
            ->method('save')
            ->willReturnCallback(function (Appointment $appointment): Appointment {
                $appointment->assignId(700);

                return $appointment;
            });

        $this->availabilityRepository
            ->expects(self::once())
            ->method('findAvailableEmployeeIdForServiceAt')
            ->willReturn(50);

        $appointment = $this->useCase->execute($dto);

        self::assertSame(700, $appointment->id());
        self::assertSame(300, $appointment->clientId());
        self::assertTrue($appointment->reminderPreferences()->remind1HourBefore());
    }

    private function makeDto(string $phone): ScheduleAppointmentDTO
    {
        return new ScheduleAppointmentDTO(
            firstName: 'Ana',
            lastName: 'Perez',
            dni: '30123456',
            phone: $phone,
            email: 'ana@example.com',
            serviceId: 10,
            scheduledAt: new DateTimeImmutable('+2 days'),
            remind1DayBefore: true,
            remind30MinsBefore: false,
        );
    }
}
