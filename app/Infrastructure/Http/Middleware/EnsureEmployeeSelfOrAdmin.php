<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Middleware;

use App\Domain\User\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureEmployeeSelfOrAdmin
{
    /**
     * Allow admins to act on any employee id; employees only on themselves.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user === null) {
            // Normally handled by auth:sanctum, but keep it explicit.
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (($user->role ?? null) === UserRole::Admin) {
            return $next($request);
        }

        $routeEmployeeId = $request->route('id');
        if ($routeEmployeeId === null) {
            return $next($request);
        }

        if ((int) $routeEmployeeId !== (int) $user->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        return $next($request);
    }
}
