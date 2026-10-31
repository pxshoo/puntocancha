<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureClubAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->club_id && in_array($request->user()->role, ['club_admin', 'staff'], true), 403);

        return $next($request);
    }
}
