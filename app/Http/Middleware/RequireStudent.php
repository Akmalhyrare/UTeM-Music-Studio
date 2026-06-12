<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RequireStudent
{
    public function handle(Request $request, Closure $next)
    {
        if (session('user_type') !== 'student') {
            return redirect()->route('login');
        }

        return $next($request);
    }
}
