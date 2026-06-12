<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RequireAdmin
{
    public function handle(Request $request, Closure $next)
    {
        if (session('user_type') !== 'staff' || !session('is_admin')) {
            return redirect()->route('login');
        }

        return $next($request);
    }
}
