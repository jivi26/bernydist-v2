<?php

namespace App\Http\Middleware;

use App\Services\UserSessionService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Re-popula session('user') y session('cart') cuando el usuario está autenticado
 * via remember_token pero la sesión expiró (por ejemplo, nuevo tab o sesión caducada).
 */
class EnsureUserSession
{
    public function __construct(protected UserSessionService $sessionService) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && ! $request->session()->has('user')) {
            $this->sessionService->initSession(Auth::user()->cliente_id);
        }

        return $next($request);
    }
}
