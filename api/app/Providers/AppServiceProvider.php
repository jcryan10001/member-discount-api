<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Behind Render's TLS-terminating proxy the app receives plain HTTP, so
        // url()/route()/asset() would emit http:// links (mixed-content warnings,
        // broken redirects). Force https only in production; local dev stays http.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // A simple admin check rather than a full RBAC system. Applied as
        // ->middleware('can:admin') on the admin route group. (.NET equivalent:
        // an [Authorize(Roles = "Admin")] policy.)
        Gate::define('admin', fn (User $user): bool => $user->is_admin);
    }
}
