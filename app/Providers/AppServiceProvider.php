<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;
use App\Models\Invoice;
use App\Observers\InvoiceObserver;


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
        Gate::before(function ($user = null) {
            if (!$user && Auth::guard('team_person')->check()) {
                return Gate::forUser(Auth::guard('team_person')->user());
            }
        });

        /** Set the Authenticate User Guard - Start  */
        $authGuard = null;
        foreach (config('auth.guards') as $guard => $values) {
            if (Auth::guard($guard)->check()) {
                // dd($guard, $values);
                $authGuard = $guard;
                break;
            }
        }
        View::composer('*', function ($view) use ($authGuard) {
            $view->with('authenticateUserGuard', $authGuard);
        });
        /** End */

        /** Access in any where */
        View::composer('*', function ($view) use ($authGuard) {
            $authUserDetail = ($authGuard) ? Auth::guard($authGuard)?->user(): Auth::user();
            $authUserDetail['user_guard'] = $authGuard;
            // if(count((array)$authUserDetail) > 0){
            //     $authUserDetail = (array)$authUserDetail;
            // }
            $view->with('authenticateUserDetails', $authUserDetail);
            // Session::put('company_id', ($authUserDetail?->company_id) ? $authUserDetail?->company_id : null);
        });

        View::share('firebaseConfig', config('constants.firebase'));

        // Register Invoice Observer
        Invoice::observe(InvoiceObserver::class);

        // Passport::tokensExpireIn(now()->addDays(15));
        // Passport::refreshTokensExpireIn(now()->addDays(30));
        // Passport::personalAccessTokensExpireIn(now()->addMonths(6));
    }
}
