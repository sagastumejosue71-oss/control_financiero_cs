<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
        ]);
        $middleware->web(append: [
            \App\Http\Middleware\RememberMeMiddleware::class,
            \App\Http\Middleware\SecurityHeadersMiddleware::class,
        ]);

        // Detrás de un reverse proxy / load balancer (Nginx, Cloudflare, etc.)
        // que termina el HTTPS, Laravel necesita saber en cuáles confiar para
        // leer la IP y el esquema reales desde X-Forwarded-*. Sin esto, el
        // rate limiter de login agrupa a todos los usuarios bajo la IP del
        // proxy y el header HSTS nunca se activa. Se activa solo si se define
        // TRUSTED_PROXIES en el .env del servidor; en local no cambia nada.
        if ($trustedProxies = env('TRUSTED_PROXIES')) {
            $middleware->trustProxies(
                at: $trustedProxies === '*' ? '*' : explode(',', $trustedProxies)
            );
        }
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
