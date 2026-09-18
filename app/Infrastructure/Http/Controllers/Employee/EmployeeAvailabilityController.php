<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers\Employee;

use App\Infrastructure\Persistence\Eloquent\Models\EmployeeBlockedDateModel;
use App\Infrastructure\Persistence\Eloquent\Models\EmployeeBreakModel;
use App\Infrastructure\Persistence\Eloquent\Models\EmployeeScheduleModel;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class EmployeeAvailabilityController
{
    private static function normalizeTime(mixed $value): string
    {
        $s = (string) $value;

        // MySQL TIME comes back as HH:MM:SS; the UI expects HH:MM.
        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $s) === 1) {
            return substr($s, 0, 5);
        }

        return $s;
    }

    public function show(int $id): JsonResponse
    {
        $user = User::query()->find($id);
        if ($user === null) {
            return response()->json(['message' => 'Employee not found.'], 404);
        }

        return response()->json([
            'data' => [
                'user' => [
                    'id' => (int) $user->id,
                    'name' => (string) $user->name,
                    'email' => (string) $user->email,
                    'role' => (string) $user->role->value,
                ],
                'service_ids' => $user->services()->pluck('services.id')->map(fn ($v) => (int) $v)->all(),
                'schedules' => EmployeeScheduleModel::query()
                    ->where('user_id', $id)
                    ->orderBy('day_of_week')
                    ->orderBy('start_time')
                    ->get(['day_of_week', 'start_time', 'end_time'])
                    ->map(fn ($r) => [
                        'day_of_week' => (int) $r->day_of_week,
                        'start_time' => self::normalizeTime($r->start_time),
                        'end_time' => self::normalizeTime($r->end_time),
                    ])->all(),
                'breaks' => EmployeeBreakModel::query()
                    ->where('user_id', $id)
                    ->orderBy('day_of_week')
                    ->orderBy('start_time')
                    ->get(['day_of_week', 'start_time', 'end_time'])
                    ->map(fn ($r) => [
                        'day_of_week' => (int) $r->day_of_week,
                        'start_time' => self::normalizeTime($r->start_time),
                        'end_time' => self::normalizeTime($r->end_time),
                    ])->all(),
                'blocked_dates' => EmployeeBlockedDateModel::query()
                    ->where('user_id', $id)
                    ->orderBy('date')
                    ->get(['date', 'reason'])
                    ->map(fn ($r) => [
                        'date' => $r->date instanceof \DateTimeInterface ? $r->date->format('Y-m-d') : (string) $r->date,
                        'reason' => $r->reason !== null ? (string) $r->reason : null,
                    ])->all(),
            ],
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $user = User::query()->find($id);
        if ($user === null) {
            return response()->json(['message' => 'Employee not found.'], 404);
        }

        $validated = $request->validate([
            'service_ids' => ['present', 'array'],
            'service_ids.*' => ['integer', 'min:1'],
            'schedules' => ['present', 'array'],
            'schedules.*.day_of_week' => ['required', 'integer', 'between:0,6'],
            'schedules.*.start_time' => ['required', 'date_format:H:i'],
            'schedules.*.end_time' => ['required', 'date_format:H:i', 'after:schedules.*.start_time'],
            'breaks' => ['present', 'array'],
            'breaks.*.day_of_week' => ['required', 'integer', 'between:0,6'],
            'breaks.*.start_time' => ['required', 'date_format:H:i'],
            'breaks.*.end_time' => ['required', 'date_format:H:i', 'after:breaks.*.start_time'],
        ]);

        DB::transaction(function () use ($user, $id, $validated): void {
            $serviceIds = array_values(array_unique(array_map('intval', $validated['service_ids'] ?? [])));
            $user->services()->sync($serviceIds);

            EmployeeScheduleModel::query()->where('user_id', $id)->delete();
            foreach (($validated['schedules'] ?? []) as $row) {
                EmployeeScheduleModel::query()->create([
                    'user_id' => $id,
                    'day_of_week' => (int) $row['day_of_week'],
                    'start_time' => (string) $row['start_time'],
                    'end_time' => (string) $row['end_time'],
                ]);
            }

            EmployeeBreakModel::query()->where('user_id', $id)->delete();
            foreach (($validated['breaks'] ?? []) as $row) {
                EmployeeBreakModel::query()->create([
                    'user_id' => $id,
                    'day_of_week' => (int) $row['day_of_week'],
                    'start_time' => (string) $row['start_time'],
                    'end_time' => (string) $row['end_time'],
                ]);
            }
        });

        return response()->json(['data' => ['message' => 'Availability updated.']]);
    }
}
