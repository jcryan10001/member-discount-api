<?php

use App\Http\Controllers\SpaController;
use Illuminate\Support\Facades\Route;

// Serve the built React SPA for every non-API path so client-side routing and
// deep links work. `/api/*` is handled by routes/api.php and takes precedence;
// the regex explicitly excludes it so an unknown /api/* path still returns a
// JSON 404 instead of the SPA shell.
Route::get('/{any?}', SpaController::class)->where('any', '^(?!api).*$');
