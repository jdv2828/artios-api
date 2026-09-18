<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Appointment;

use App\Application\Appointment\DTO\AppointmentSearchResultDTO;
use App\Application\Appointment\DTO\ListAppointmentsDTO;
use App\Application\Appointment\Ports\AppointmentRepositoryInterface;
use App\Application\Appointment\UseCases\ListAppointmentsUseCase;
use App\Domain\Appointment\Appointment;
use App\Domain\Appointment\ReminderPreferences;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ListAppointmentsUseCaseTest extends TestCase
{
    private AppointmentRepositoryInterface&MockObject $repository;
    private ListAppointmentsUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(AppointmentRepositoryInterface::class);
        $this->useCase = new ListAppointmentsUseCase($this->repository);
    }

    public function test_it_returns_filtered_results(): void
    {
        $appointment = Appointment::schedule(1, 1, 1, new DateTimeImmutable('+1 day'), new ReminderPreferences(true, false));
        $appointment->assignId(10);

        $result = new AppointmentSearchResultDTO([$appointment], 1, 1, 15, 1);

        $this->repository
            ->expects(self::once())
            ->method('findAllFiltered')
            ->with(self::isInstanceOf(ListAppointmentsDTO::class))
            ->willReturn($result);

        self::assertSame(1, $this->useCase->execute(new ListAppointmentsDTO())->total);
    }
}
