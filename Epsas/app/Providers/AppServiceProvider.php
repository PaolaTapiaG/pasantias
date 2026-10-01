<?php

namespace App\Providers;

use App\Auth\CachedEloquentUserProvider;
use App\Models\SystemSetting;
use App\Support\OperationalCache;
use App\Support\UserSessionSecurity;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rules\Password;
use Exception;

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
        if (config('production.enforce_https')) {
            URL::forceScheme('https');
        }

        Password::defaults(fn () => UserSessionSecurity::passwordRule());

        Auth::provider('cached_eloquent', function ($app, array $config) {
            return new CachedEloquentUserProvider($app['hash'], $config['model']);
        });

        View::composer('*', function ($view) {
            static $sharedCompanySettings;
            static $sharedAuthUserResolved = false;
            static $sharedAuthUser;
            $defaultCompanySettings = [
                'company_name' => 'EPSAS',
                'company_alias' => 'Panel administrativo',
                'company_logo' => null,
            ];
            $viewName = (string) $view->getName();
            $usesExplicitPageSettings = str_starts_with($viewName, 'auth.')
                || str_starts_with($viewName, 'errors.')
                || str_starts_with($viewName, 'laravel-exceptions')
                || request()->routeIs('login', 'password.*');

            if ($usesExplicitPageSettings) {
                $view->with('sharedCompanySettings', $defaultCompanySettings);
                $view->with('sharedAuthUser', null);

                return;
            }

            $sharedCompanySettings ??= OperationalCache::rememberDomain('settings', 'shared.company-settings', function () use ($defaultCompanySettings) {
                try {
                    return SystemSetting::getValue('general', $defaultCompanySettings);
                } catch (Exception $e) {
                    return $defaultCompanySettings;
                }
            });

            if (!$sharedAuthUserResolved) {
                try {
                    $sharedAuthUser = auth()->check()
                        ? auth()->user()
                        : null;
                } catch (Exception $e) {
                    // If auth check fails, treat as not authenticated
                    $sharedAuthUser = null;
                }
                $sharedAuthUserResolved = true;
            }

            $view->with('sharedCompanySettings', $sharedCompanySettings);
            $view->with('sharedAuthUser', $sharedAuthUser);
        });
    }
}
