<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Adapters;

use App\Application\Availability\Ports\AvailabilityRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\Models\AppointmentModel;
use App\Infrastructure\Persistence\Eloquent\Models\EmployeeBlockedDateModel;
use App\Infrastructure\Persistence\Eloquent\Models\EmployeeBreakModel;
use App\Infrastructure\Persistence\Eloquent\Models\EmployeeScheduleModel;
use App\Infrastructure\Persistence\Eloquent\Models\ServiceModel;
use App\Models\User;
use DateTimeImmutable;

final class EloquentAvailabilityRepository implements AvailabilityRepositoryInterface
{
    public function listAvailableSlotsForServiceOnDate(int $serviceId, DateTimeImmutable $date): array
    {
        $service = ServiceModel::query()->find($serviceId);
        if ($service === null || ! $service->active) {
            return [];
        }

        $durationMinutes = (int) $service->duration_minutes;
        if ($durationMinutes <= 0) {
            return [];
        }

        $dayOfWeek = (int) $date->format('w');
        $now = new DateTimeImmutable();
        $isToday = $now->format('Y-m-d') === $date->format('Y-m-d');
        $nowMin = ((int) $now->format('H')) * 60 + (int) $now->format('i');
        $employees = User::query()
            ->whereHas('services', fn ($q) => $q->whereKey($serviceId))
            ->get(['id']);

        if ($employees->isEmpty()) {
            return [];
        }

        $stepMinutes = 30; // fixed grid
        $candidateTimes = [];
        for ($t = 6 * 60; $t <= (22 * 60); $t += $stepMinutes) {
            $candidateTimes[] = $t;
        }

        /** @var array<int, array{time:string, available:bool, in_schedule:bool}> $resultsByStart */
        $resultsByStart = [];
        foreach ($candidateTimes as $startMin) {
            $resultsByStart[$startMin] = [
                'time' => self::minutesToTime($startMin),
                'available' => false,
                'in_schedule' => false,
            ];
        }

        foreach ($employees as $employee) {
            $employeeId = (int) $employee->id;

            $scheduleIntervals = $this->scheduleIntervalsForDay($employeeId, $dayOfWeek);
            if ($scheduleIntervals === []) {
                continue;
            }

            // Mark slots that exist in the employee schedule (even if later unavailable due to breaks).
            foreach ($candidateTimes as $startMin) {
                $endMin = $startMin + $durationMinutes;
                if (self::isWithinAnyInterval($startMin, $endMin, $scheduleIntervals)) {
                    $resultsByStart[$startMin]['in_schedule'] = true;
                }
            }

            if ($this->isBlockedDate($employeeId, $date)) {
                continue;
            }

            $workingIntervals = $this->workingIntervalsForDay($employeeId, $dayOfWeek);
            if ($workingIntervals === []) {
                continue;
            }

            $bookedIntervals = $this->bookedIntervalsForDate($employeeId, $date);

            foreach ($candidateTimes as $startMin) {
                if ($resultsByStart[$startMin]['available'] === true) {
                    continue;
                }

                $slotStart = $startMin;
                $slotEnd = $slotStart + $durationMinutes;

                if ($isToday && $slotStart <= $nowMin) {
                    continue;
                }

                if (! self::isWithinAnyInterval($slotStart, $slotEnd, $workingIntervals)) {
                    continue;
                }

                if (self::overlapsAnyInterval($slotStart, $slotEnd, $bookedIntervals)) {
                    continue;
                }

                $resultsByStart[$startMin]['available'] = true;
            }
        }

        // Only return slots that exist in at least one employee schedule.
        $final = array_values(array_filter($resultsByStart, fn (array $slot): bool => $slot['in_schedule']));
        foreach ($final as &$slot) {
            unset($slot['in_schedule']);
        }

        return $final;
    }

    public function findAvailableEmployeeIdForServiceAt(int $serviceId, DateTimeImmutable $scheduledAt): ?int
    {
        $service = ServiceModel::query()->find($serviceId);
        if ($service === null || ! $service->active) {
            return null;
        }

        $durationMinutes = (int) $service->duration_minutes;
        if ($durationMinutes <= 0) {
            return null;
        }

        $date = new DateTimeImmutable($scheduledAt->format('Y-m-d'));
        $dayOfWeek = (int) $scheduledAt->format('w');
        $slotStart = ((int) $scheduledAt->format('H')) * 60 + (int) $scheduledAt->format('i');
        $slotEnd = $slotStart + $durationMinutes;

        $employees = User::query()
            ->whereHas('services', fn ($q) => $q->whereKey($serviceId))
            ->orderBy('id')
            ->get(['id']);

        foreach ($employees as $employee) {
            $employeeId = (int) $employee->id;

            if ($this->isBlockedDate($employeeId, $date)) {
                continue;
            }

            $workingIntervals = $this->workingIntervalsForDay($employeeId, $dayOfWeek);
            if ($workingIntervals === []) {
                continue;
            }

            if (! self::isWithinAnyInterval($slotStart, $slotEnd, $workingIntervals)) {
                continue;
            }

            $bookedIntervals = $this->bookedIntervalsForDate($employeeId, $date);
            if (self::overlapsAnyInterval($slotStart, $slotEnd, $bookedIntervals)) {
                continue;
            }

            return $employeeId;
        }

        return null;
    }

    /**
     * @return array<int, array{0:int,1:int}>
     */
    private function workingIntervalsForDay(int $employeeId, int $dayOfWeek): array
    {
        $scheduleIntervals = $this->scheduleIntervalsForDay($employeeId, $dayOfWeek);
        if ($scheduleIntervals === []) {
            return [];
        }

        $breaks = EmployeeBreakModel::query()
            ->where('user_id', $employeeId)
            ->where('day_of_week', $dayOfWeek)
            ->orderBy('start_time')
            ->get(['start_time', 'end_time']);

        $breakIntervals = [];
        foreach ($breaks as $row) {
            $start = self::timeToMinutes((string) $row->start_time);
            $end = self::timeToMinutes((string) $row->end_time);
            if ($end > $start) {
                $breakIntervals[] = [$start, $end];
            }
        }

        return self::subtractIntervals($scheduleIntervals, $breakIntervals);
    }

    /**
     * @return array<int, array{0:int,1:int}>
     */
    private function scheduleIntervalsForDay(int $employeeId, int $dayOfWeek): array
    {
        $schedules = EmployeeScheduleModel::query()
            ->where('user_id', $employeeId)
            ->where('day_of_week', $dayOfWeek)
            ->orderBy('start_time')
            ->get(['start_time', 'end_time']);

        if ($schedules->isEmpty()) {
            return [];
        }

        $scheduleIntervals = [];
        foreach ($schedules as $row) {
            $start = self::timeToMinutes((string) $row->start_time);
            $end = self::timeToMinutes((string) $row->end_time);
            if ($end > $start) {
                $scheduleIntervals[] = [$start, $end];
            }
        }

        return $scheduleIntervals;
    }

    /**
     * @return array<int, array{0:int,1:int}>
     */
    private function bookedIntervalsForDate(int $employeeId, DateTimeImmutable $date): array
    {
        $start = $date->setTime(0, 0);
        $end = $date->setTime(23, 59, 59);

        $appointments = AppointmentModel::query()
            ->with('service')
            ->where('employee_id', $employeeId)
            ->whereBetween('scheduled_at', [$start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')])
            ->where('status', '!=', 'cancelled')
            ->get(['scheduled_at', 'service_id', 'status']);

        $intervals = [];
        foreach ($appointments as $appointment) {
            $scheduledAt = $appointment->scheduled_at;
            if (! $scheduledAt instanceof \DateTimeInterface) {
                continue;
            }

            $startMin = ((int) $scheduledAt->format('H')) * 60 + (int) $scheduledAt->format('i');
            $dur = $appointment->service ? (int) $appointment->service->duration_minutes : 0;
            if ($dur <= 0) {
                $dur = 30;
            }
            $intervals[] = [$startMin, $startMin + $dur];
        }

        return $intervals;
    }

    private function isBlockedDate(int $employeeId, DateTimeImmutable $date): bool
    {
        return EmployeeBlockedDateModel::query()
            ->where('user_id', $employeeId)
            ->whereDate('date', $date->format('Y-m-d'))
            ->exists();
    }

    private static function timeToMinutes(string $time): int
    {
        $time = trim($time);
        // Accept HH:MM or HH:MM:SS.
        $parts = explode(':', $time);
        $h = (int) ($parts[0] ?? 0);
        $m = (int) ($parts[1] ?? 0);
        return $h * 60 + $m;
    }

    private static function minutesToTime(int $minutes): string
    {
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;
        return str_pad((string) $h, 2, '0', STR_PAD_LEFT).':'.str_pad((string) $m, 2, '0', STR_PAD_LEFT);
    }

    /**
     * @param array<int, array{0:int,1:int}> $containerIntervals
     */
    private static function isWithinAnyInterval(int $startMin, int $endMin, array $containerIntervals): bool
    {
        foreach ($containerIntervals as [$start, $end]) {
            if ($startMin >= $start && $endMin <= $end) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<int, array{0:int,1:int}> $intervals
     */
    private static function overlapsAnyInterval(int $startMin, int $endMin, array $intervals): bool
    {
        foreach ($intervals as [$start, $end]) {
            if ($startMin < $end && $endMin > $start) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<int, array{0:int,1:int}> $base
     * @param array<int, array{0:int,1:int}> $subtract
     * @return array<int, array{0:int,1:int}>
     */
    private static function subtractIntervals(array $base, array $subtract): array
    {
        $result = $base;

        foreach ($subtract as [$subStart, $subEnd]) {
            $next = [];
            foreach ($result as [$start, $end]) {
                // No overlap.
                if ($subEnd <= $start || $subStart >= $end) {
                    $next[] = [$start, $end];
                    continue;
                }

                // Left remainder.
                if ($subStart > $start) {
                    $next[] = [$start, $subStart];
                }

                // Right remainder.
                if ($subEnd < $end) {
                    $next[] = [$subEnd, $end];
                }
            }
            $result = $next;
        }

        // Normalize: remove invalid and sort.
        $result = array_values(array_filter($result, fn (array $i): bool => $i[1] > $i[0]));
        usort($result, fn (array $a, array $b): int => $a[0] <=> $b[0]);

        return $result;
    }
}
