<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureUserRole
{
    /**
     * @param  array<int, string>  $roles
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = $request->user();

        if (!$user || (count($roles) > 0 && !in_array($user->role, $roles, true))) {
            abort(403);
        }

        return $next($request);
    }
}
