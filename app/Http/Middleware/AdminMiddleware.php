<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!session('user_id')) {
            return $request->expectsJson()
                ? response()->json(['error' => 'No autenticado'], 401)
                : redirect('/login');
        }

        $user = \App\Models\User::find(session('user_id'));
        if (!$user || !$user->isAdmin() || !$user->isActive()) {
            return $request->expectsJson()
                ? response()->json(['error' => 'No autorizado'], 403)
                : redirect('/finanzas')->with('error', 'No tienes permisos de administrador.');
        }

        return $next($request);
    }
}
