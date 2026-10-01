<?php

namespace App\Console\Commands;

use App\Auth\CachedEloquentUserProvider;
use App\Http\Controllers\Api\AdminNotificationController;
use App\Http\Controllers\CobroController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmpleadoController;
use App\Http\Controllers\FacturaController;
use App\Http\Controllers\GastoController;
use App\Http\Controllers\LecturaController;
use App\Http\Controllers\MedidorController;
use App\Http\Controllers\PaymentOrderController;
use App\Http\Controllers\SecretariaOperacionController;
use App\Http\Controllers\SocioController;
use App\Http\Controllers\TarifaController;
use App\Models\SystemSetting;
use App\Models\User;
use App\Support\OperationalCache;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class WarmApplicationCache extends Command
{
    protected $signature = 'app:performance-warm';

    protected $description = 'Prepara las consultas frecuentes para que login y navegacion respondan rapido';

    public function handle(
        AdminNotificationController $notifications,
        DashboardController $dashboard,
        EmpleadoController $employees,
        SocioController $socios,
        MedidorController $meters,
        LecturaController $readings,
        FacturaController $invoices,
        CobroController $payments,
        GastoController $expenses,
        TarifaController $tariffs,
        PaymentOrderController $paymentOrders,
        SecretariaOperacionController $secretaryOperations
    ): int {
        $this->info('Preparando usuarios, paneles y modulos frecuentes...');

        $company = SystemSetting::getValue('general', [
            'company_name' => 'EPSAS',
            'company_alias' => 'Panel administrativo',
            'company_logo' => null,
        ]);
        Cache::forget('shared.company-settings');
        OperationalCache::rememberDomain('settings', 'shared.company-settings', fn () => $company);
        User::query()
            ->with(['persona', 'roles:id,name'])
            ->get()
            ->each(function (User $user) {
                Cache::put(
                    'auth:user:'.str_replace('\\', '.', $user::class).':'.$user->getAuthIdentifier(),
                    $user,
                    now()->addDay()
                );
                Cache::put("user:{$user->getKey()}:role-names", $user->roles->pluck('name'), now()->addDay());

                if ($user->email) {
                    Cache::put(
                        CachedEloquentUserProvider::credentialCacheKey(['email' => mb_strtolower($user->email)]),
                        $user,
                        now()->addDay()
                    );
                }

                if ($user->username) {
                    Cache::put(
                        CachedEloquentUserProvider::credentialCacheKey(['username' => mb_strtolower($user->username)]),
                        $user,
                        now()->addDay()
                    );
                }
            });

        $dashboard->warmForRoles(collect(['administrador', 'secretaria', 'tecnico']));
        $employees->warmIndexCache();
        $socios->warmIndexCache();
        $meters->warmIndexCache();
        $readings->warmIndexCache();
        $invoices->warmIndexCache();
        $payments->warmIndexCache();
        $expenses->warmIndexCache();
        $tariffs->warmIndexCache();
        $paymentOrders->warmIndexCache();
        $secretaryOperations->warmIndexCache();
        $notifications->warmCache();
        $this->info('Cache de rendimiento preparada.');

        return self::SUCCESS;
    }
}
