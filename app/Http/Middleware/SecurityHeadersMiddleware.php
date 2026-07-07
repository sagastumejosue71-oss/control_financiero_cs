<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeadersMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Evita que la app se incruste en iframes (clickjacking)
        $response->headers->set('X-Frame-Options', 'DENY');

        // Bloquea MIME-type sniffing (evita XSS via archivos mal tipados)
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Política de referencia: no filtrar URLs internas a terceros
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Deshabilita APIs sensibles del navegador que la app no usa
        $response->headers->set(
            'Permissions-Policy',
            'camera=(), microphone=(), geolocation=(), payment=(), usb=(), bluetooth=()'
        );

        // Content Security Policy
        // 'unsafe-inline' requerido por Livewire y Vite en desarrollo.
        // Para producción HTTPS considera reemplazarlo por nonces.
        $response->headers->set('Content-Security-Policy', implode(' ', [
            "default-src 'self';",
            "script-src 'self' 'unsafe-inline' https://accounts.google.com https://apis.google.com;",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com;",
            "font-src 'self' https://fonts.gstatic.com data:;",
            "img-src 'self' data: https://lh3.googleusercontent.com https://lh4.googleusercontent.com https://lh5.googleusercontent.com;",
            "connect-src 'self';",
            "frame-src https://accounts.google.com;",
            "frame-ancestors 'none';",
            "form-action 'self';",
            "base-uri 'self';",
            "object-src 'none';",
        ]));

        // HSTS: fuerza HTTPS por 1 año (solo activa si la conexión es segura)
        if ($request->isSecure()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains; preload'
            );
        }

        // Oculta la tecnología del servidor
        $response->headers->remove('X-Powered-By');
        $response->headers->remove('Server');

        return $response;
    }
}
