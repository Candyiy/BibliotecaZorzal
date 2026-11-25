<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsActive
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if (! $usuario) {
            return $next($request);
        }

        $usuario->sincronizarEstado();

        if ($usuario->estaSuspendido()) {
            if ($request->expectsJson()) {
                abort(403, 'Tu cuenta está suspendida temporalmente.');
            }

            return redirect()->route('cuenta.suspendida');
        }

        return $next($request);
    }
}
