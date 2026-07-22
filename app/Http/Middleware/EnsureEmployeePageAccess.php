<?php

namespace App\Http\Middleware;

use App\Models\SystemSetting;
use Closure;
use Illuminate\Http\Request;

class EnsureEmployeePageAccess
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (! $user || $user->role === 'admin') {
            return $next($request);
        }

        $settings = SystemSetting::current();

        if (! $settings->allowsEmployeeRoute($request->route()?->getName())) {
            abort(403, 'You do not have permission to access this page.');
        }

        return $next($request);
    }
}
