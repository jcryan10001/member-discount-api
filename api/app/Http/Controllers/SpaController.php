<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\Response;

class SpaController extends Controller
{
    /**
     * Serve the built React app's index.html for any non-API route, so the
     * client-side router can handle deep links and page refreshes.
     *
     * This is an invokable controller rather than a route closure on purpose:
     * `php artisan route:cache` (run during the Docker build) cannot serialise
     * closures, so a closure here would break the build. A controller class
     * reference caches fine.
     */
    public function __invoke(): Response
    {
        $index = public_path('index.html');

        // Fail gracefully before the frontend has been built (fresh clone / CI).
        if (! is_file($index)) {
            return response('Frontend not built yet. Run `npm run build` in /web.', 503);
        }

        return response()->file($index);
    }
}
