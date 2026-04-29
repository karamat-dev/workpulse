<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * @param  array<int, string>  ...$roles
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(401);
        }

        $currentRole = method_exists($user, 'canonicalRole') ? $user->canonicalRole() : $user->role;
        if (!in_array($currentRole, $roles, true)) {
            abort(403);
        }

        return $next($request);
    }
}

