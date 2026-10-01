<?php

namespace App\Console\Commands;

use App\Services\BillingAutomationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SyncCurrentInvoices extends Command
{
    protected $signature = 'billing:sync-current-invoices {--force : Ignora la guarda temporal y recalcula la sincronizacion}';

    protected $description = 'Sincroniza facturas vigentes pendientes fuera de las pantallas web.';

    public function handle(BillingAutomationService $billing): int
    {
        $result = null;

        Cache::lock('billing-sync-current-invoices', 600)->block(5, function () use ($billing, &$result) {
            $result = $billing->ensureCurrentInvoices(null, (bool) $this->option('force'));
        });

        $created = (int) ($result['created'] ?? 0);
        $skipped = (int) ($result['skipped'] ?? 0);

        $this->info("Sincronizacion completada. Creadas: {$created}. Omitidas: {$skipped}.");

        return self::SUCCESS;
    }
}
