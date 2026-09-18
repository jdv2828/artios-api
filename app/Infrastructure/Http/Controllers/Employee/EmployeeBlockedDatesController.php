<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers\Employee;

use App\Infrastructure\Persistence\Eloquent\Models\EmployeeBlockedDateModel;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class EmployeeBlockedDatesController
{
    public function store(Request $request, int $id): JsonResponse
    {
        $user = User::query()->find($id);
        if ($user === null) {
            return response()->json(['message' => 'Employee not found.'], 404);
        }

        $validated = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        EmployeeBlockedDateModel::query()->updateOrCreate(
            ['user_id' => $id, 'date' => (string) $validated['date']],
            ['reason' => $validated['reason'] ?? null],
        );

        return response()->json(['data' => ['message' => 'Blocked date saved.']], 201);
    }

    public function destroy(int $id, string $date): JsonResponse
    {
        $user = User::query()->find($id);
        if ($user === null) {
            return response()->json(['message' => 'Employee not found.'], 404);
        }

        EmployeeBlockedDateModel::query()
            ->where('user_id', $id)
            ->whereDate('date', $date)
            ->delete();

        return response()->json([], 204);
    }
}
