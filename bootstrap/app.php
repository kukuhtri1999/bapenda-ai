<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Trust all upstream proxies (Cloudflare, cPanel Nginx/Apache reverse proxies)
        // This is CRITICAL for correct HTTPS detection, secure cookie handling, and fixing 419 Page Expired
        $middleware->trustProxies(at: '*');

        $middleware->web(append: [
            \App\Http\Middleware\SecurityHeadersMiddleware::class,
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        // Register custom middleware aliases
        $middleware->alias([
            'ensure.wajib.pajak' => \App\Http\Middleware\EnsureWajibPajakData::class,
            'secret.photo.access' => \App\Http\Middleware\EnsureSecretPhotoAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->respond(function (\Symfony\Component\HttpFoundation\Response $response, \Throwable $exception, \Illuminate\Http\Request $request) {
            if ($response->getStatusCode() === 419) {
                if ($request->expectsJson() || $request->header('X-Inertia')) {
                    return response()->json([
                        'message' => 'Sesi Anda telah kedaluwarsa, silakan muat ulang halaman.',
                        'code' => 'PAGE_EXPIRED',
                        'csrf_token' => csrf_token(),
                    ], 419);
                }
                return back()->with([
                    'message' => 'Sesi Anda telah kedaluwarsa, silakan coba kembali.',
                ]);
            }
            return $response;
        });
    })->create();
