<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Appointment;

use App\Application\Appointment\Ports\AppointmentRepositoryInterface;
use App\Application\Appointment\Ports\ClientRepositoryInterface;
use App\Application\Appointment\UseCases\ListClientAppointmentsUseCase;
use App\Application\Client\Exceptions\ClientNotFoundException;
use App\Domain\Appointment\Appointment;
use App\Domain\Appointment\ReminderPreferences;
use App\Domain\Client\Client;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ListClientAppointmentsUseCaseTest extends TestCase
{
    private ClientRepositoryInterface&MockObject $clientRepository;
    private AppointmentRepositoryInterface&MockObject $appointmentRepository;
    private ListClientAppointmentsUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clientRepository = $this->createMock(ClientRepositoryInterface::class);
        $this->appointmentRepository = $this->createMock(AppointmentRepositoryInterface::class);
        $this->useCase = new ListClientAppointmentsUseCase(
            $this->clientRepository,
            $this->appointmentRepository,
        );
    }

    public function test_it_returns_the_clients_appointments(): void
    {
        $client = new Client(1, 'Ana', 'Perez', '30123456', '+54 11 5555-8888', 'ana@example.com');
        $appointment = Appointment::schedule(1, 1, 10, new DateTimeImmutable('+1 day'), new ReminderPreferences(true, false));
        $appointment->assignId(100);

        $this->clientRepository
            ->expects(self::once())
            ->method('findByDni')
            ->with('30123456')
            ->willReturn($client);

        $this->appointmentRepository
            ->expects(self::once())
            ->method('findByClientId')
            ->with(1)
            ->willReturn([$appointment]);

        $appointments = $this->useCase->execute('30123456');

        self::assertCount(1, $appointments);
        self::assertSame(100, $appointments[0]->id());
    }

    public function test_it_throws_when_client_does_not_exist(): void
    {
        $this->clientRepository
            ->expects(self::once())
            ->method('findByDni')
            ->willReturn(null);

        $this->expectException(ClientNotFoundException::class);

        $this->useCase->execute('30123456');
    }
}
