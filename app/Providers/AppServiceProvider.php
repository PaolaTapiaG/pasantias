<?php

namespace App\Providers;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

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
        View::composer([
            'layouts.app',
            'dashboard.admin',
            'slideboard.sidebaradmin',
            'slideboard.sidebartec',
        ], function ($view) {
            $view->with('sharedCompanySettings', Cache::remember('shared_company_settings', now()->addMinutes(30), function () {
                return SystemSetting::getValue('general', [
                    'company_name' => 'EPSAS',
                    'company_alias' => 'Panel administrativo',
                    'company_logo' => null,
                ]);
            }));

            $view->with('sharedAuthUser', auth()->check() ? auth()->user()->loadMissing('persona') : null);
        });
    }
}
