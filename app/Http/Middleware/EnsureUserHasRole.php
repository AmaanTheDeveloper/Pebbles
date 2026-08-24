<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user === null || $roles === []) {
            abort(Response::HTTP_FORBIDDEN, 'This action is unauthorized.');
        }

        foreach ($roles as $role) {
            $requiredRole = UserRole::tryFrom($role);

            if ($requiredRole !== null && $user->hasRole($requiredRole)) {
                return $next($request);
            }
        }

        abort(Response::HTTP_FORBIDDEN, 'This action is unauthorized.');
    }
}
