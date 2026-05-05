<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordChanged
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->password_must_change || $this->isAllowedWhileLocked($request)) {
            return $next($request);
        }

        return new JsonResponse([
            'ok' => false,
            'message' => 'Please set a new password before continuing.',
            'passwordChangeRequired' => true,
        ], 423, [
            'Cache-Control' => 'no-store, private',
        ]);
    }

    private function isAllowedWhileLocked(Request $request): bool
    {
        return $request->is('api/bootstrap')
            || $request->is('api/me/account')
            || $request->is('logout');
    }
}
