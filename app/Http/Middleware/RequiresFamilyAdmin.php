<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RequiresFamilyAdmin
{
    public function handle(Request $request, Closure $next)
    {
        if (auth()->user()->isFamilyAdmin($request->route('family'))) {
            return $next($request);
        }

        return response()->view('pages.403');
    }
}
