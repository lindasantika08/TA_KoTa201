<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckRole
{
    public function handle(Request $request, Closure $next, $role)
    {
        if (!$request->user() || $request->user()->role !== $role) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return $next($request);
    }
}