<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Treat every API request as wanting JSON, even when the caller didn't send an
 * `Accept: application/json` header (e.g. a bare curl). Without this, an
 * unauthenticated request falls into Laravel's web flow, which tries to redirect
 * guests to a non-existent "login" route and 500s. Forcing the header up front
 * guarantees clean JSON errors (401, 422, and so on) across the whole API.
 */
class ForceJsonAccept
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->headers->set('Accept', 'application/json');

        return $next($request);
    }
}
