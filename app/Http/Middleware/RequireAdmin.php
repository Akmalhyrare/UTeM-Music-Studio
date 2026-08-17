<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RequireAdmin
{
    public function handle(Request $request, Closure $next)
    {
        if (session('user_type') !== 'staff') {
            return redirect()->route('login');
        }

        if (!session('is_admin')) {
            return redirect()->route('staff.dashboard')
                ->with('error', 'You do not have permission to access that page.');
        }

        return $next($request);
    }
}
