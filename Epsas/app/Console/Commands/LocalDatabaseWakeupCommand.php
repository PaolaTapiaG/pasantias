<?php

namespace App\Console\Commands;

use App\Support\LocalDatabaseWakeup;
use Illuminate\Console\Command;

class LocalDatabaseWakeupCommand extends Command
{
    protected $signature = 'app:local-db-wakeup
        {--full : Tambien recalienta dashboards y modulos principales}
        {--watch : Mantiene la conexion local activa cada cierto intervalo}
        {--interval=60 : Segundos entre cada pulso en modo watch}';

    protected $description = 'Despierta la conexion PostgreSQL remota usada en desarrollo local';

    public function handle(): int
    {
        if (! app()->environment('local')) {
            $this->warn('Este comando esta pensado solo para entorno local.');

            return self::SUCCESS;
        }

        $fullPending = (bool) $this->option('full');

        do {
            try {
                $ms = LocalDatabaseWakeup::run(purge: true);
                $this->info('Conexion local despierta en '.number_format($ms, 2).' ms.');

                if ($fullPending) {
                    $this->call('app:performance-warm');
                    $fullPending = false;
                }
            } catch (\Throwable $exception) {
                $this->error('No se pudo despertar la conexion: '.$exception->getMessage());

                return self::FAILURE;
            }

            if (! $this->option('watch')) {
                break;
            }

            sleep(max(15, (int) $this->option('interval')));
        } while (true);

        return self::SUCCESS;
    }
}
