<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RequireStaff
{
    public function handle(Request $request, Closure $next)
    {
        if (session('user_type') !== 'staff') {
            return redirect()->route('login');
        }

        return $next($request);
    }
}
