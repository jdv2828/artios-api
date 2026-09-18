<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Middleware;

use App\Domain\User\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user === null) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (($user->role ?? null) !== UserRole::Admin) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        return $next($request);
    }
}
