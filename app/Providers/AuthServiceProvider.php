<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\MasterModules;
use App\Policies\PanelRightPolicy;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Laravel\Passport\Passport;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        // MasterModules::class => PanelRightPolicy::class,
    ];

    /**
    * Register services.
    */
    public function register(): void
    {

    }

    /**
    * Bootstrap services.
    */
    public function boot(): void
    {

        // Set access token expiration to 15 days
        Passport::tokensExpireIn(now()->addDays(15));

        // Optionally, set refresh token expiration
        Passport::refreshTokensExpireIn(now()->addDays(30));

        // 1) If the admin_software guard is logged in, always allow:
        Gate::before(function ($user, $ability) {
            if (Auth::guard('admin_software')->check()) {
                return true;
            }
            // returning null here lets the normal gates/policies run
            return null;
        });

        Gate::define('hasPermission', [PanelRightPolicy::class, 'hasPermission']);
    }
}
