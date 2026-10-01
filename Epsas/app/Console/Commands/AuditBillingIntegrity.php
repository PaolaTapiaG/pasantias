<?php

namespace App\Console\Commands;

use App\Services\BillingIntegrityAudit;
use Illuminate\Console\Command;

class AuditBillingIntegrity extends Command
{
    protected $signature = 'app:audit-billing-integrity';

    protected $description = 'Audita consistencia entre facturas, lecturas, estados y cobros';

    public function handle(BillingIntegrityAudit $audit): int
    {
        $checks = $audit->run();

        $this->table(
            ['Control', 'Inconsistencias'],
            collect($checks)->map(fn ($value, $key) => [$key, $value])->values()->all()
        );

        if (! $audit->isClean($checks)) {
            $this->error('La auditoria financiera detecto inconsistencias.');

            return self::FAILURE;
        }

        $this->info('Facturas y cobros consistentes.');

        return self::SUCCESS;
    }
}
