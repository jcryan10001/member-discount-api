<?php

use App\Exceptions\Redemption\RedemptionException;
use App\Http\Middleware\ForceJsonAccept;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Render (and most PaaS) terminate TLS at an upstream proxy and forward
        // over HTTP with X-Forwarded-* headers. Trusting the proxy lets Laravel
        // see the original HTTPS scheme/host so generated URLs aren't broken.
        $middleware->trustProxies(at: '*');

        // Treat all /api/* requests as wanting JSON so error responses are
        // always JSON, including the unauthenticated case, which otherwise
        // tries to redirect to a non-existent web "login" route and 500s.
        $middleware->api(prepend: [ForceJsonAccept::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        // One place maps every redemption failure to its JSON shape + status,
        // so controllers never try/catch these. Each exception knows its own
        // status() and errorCode() (see App\Exceptions\Redemption).
        $exceptions->render(function (RedemptionException $e) {
            return response()->json([
                'error' => $e->errorCode(),
                'message' => $e->getMessage(),
            ], $e->status());
        });
    })->create();
