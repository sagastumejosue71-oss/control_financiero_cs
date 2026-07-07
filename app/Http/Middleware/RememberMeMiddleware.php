<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RememberMeMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!session('user_id')) {
            $cookie = $request->cookie('remember_me');

            if ($cookie) {
                $parts = explode('|', $cookie, 2);

                if (count($parts) === 2) {
                    [$userId, $token] = $parts;
                    $userId = (int) $userId;

                    if ($userId > 0 && strlen($token) === 60) {
                        $user = User::find($userId);

                        // hash_equals: comparación en tiempo constante (evita timing attacks)
                        if ($user && $user->isActive() && $user->remember_token && hash_equals((string) $user->remember_token, (string) $token)) {
                            // Rotación de token en cada uso (previene replay attacks)
                            $newToken = Str::random(60);
                            $user->update(['remember_token' => $newToken]);

                            session(['user_id' => $user->id, 'finanzas_auth' => true]);

                            // Reemite la cookie con el nuevo token (HttpOnly + SameSite Strict)
                            cookie()->queue(
                                cookie('remember_me', $userId . '|' . $newToken, 60 * 24 * 30, '/', null, false, true, false, 'Strict')
                            );

                            return $next($request);
                        }
                    }

                    // Cookie inválida o token expirado — eliminarla
                    cookie()->queue(cookie()->forget('remember_me', '/'));
                }
            }
        }

        return $next($request);
    }
}
