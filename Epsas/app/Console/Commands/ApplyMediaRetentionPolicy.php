<?php

namespace App\Console\Commands;

use App\Models\MediaRetentionEvent;
use App\Support\PrivateMedia;
use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class ApplyMediaRetentionPolicy extends Command
{
    protected $signature = 'app:media-retention {--execute : Elimina archivos elegibles y conserva un registro de auditoria}';

    protected $description = 'Aplica la politica de retencion de fotos, evidencias tecnicas y comprobantes';

    public function handle(): int
    {
        $execute = (bool) $this->option('execute');
        $count = 0;

        $count += $this->process('foto_personal', 'personas', 'id_persona', 'foto_path',
            $this->inactivePersonPhotos(), config('media_retention.personal_photo_days_after_inactivity'), $execute);
        $count += $this->process('evidencia_anomalia', 'medidor_anomalias', 'id_anomalia', 'evidencia_path',
            $this->closedTechnicalEvidence('medidor_anomalias', 'id_anomalia', 'resuelta'), config('media_retention.technical_evidence_days_after_closure'), $execute);
        $count += $this->process('evidencia_incidencia', 'incidencias_tecnicas', 'id_incidencia', 'evidencia_path',
            $this->closedTechnicalEvidence('incidencias_tecnicas', 'id_incidencia', 'cerrada'), config('media_retention.technical_evidence_days_after_closure'), $execute);
        $count += $this->process('comprobante_pago_aprobado', 'ordenes_pago', 'id_orden_pago', 'comprobante_path',
            $this->paymentProofs(['aprobada'], config('media_retention.approved_payment_proof_days')), config('media_retention.approved_payment_proof_days'), $execute);
        $count += $this->process('comprobante_pago_no_aprobado', 'ordenes_pago', 'id_orden_pago', 'comprobante_path',
            $this->paymentProofs(['rechazada', 'cancelada', 'vencida'], config('media_retention.rejected_payment_proof_days')), config('media_retention.rejected_payment_proof_days'), $execute);

        $message = $execute ? "Politica aplicada: {$count} archivo(s) eliminados." : "Simulacion: {$count} archivo(s) elegibles.";
        $this->info($message);

        return self::SUCCESS;
    }

    private function process(
        string $type,
        string $table,
        string $idColumn,
        string $pathColumn,
        Builder $query,
        int $days,
        bool $execute
    ): int {
        $count = 0;

        $query->orderBy($idColumn)->each(function (object $row) use ($type, $table, $idColumn, $pathColumn, $days, $execute, &$count): void {
            $path = (string) $row->{$pathColumn};
            $count++;

            if (! $execute) {
                $this->line("[simulacion] {$type}: {$table} #{$row->{$idColumn}}");
                return;
            }

            DB::transaction(function () use ($type, $table, $idColumn, $pathColumn, $days, $row, $path): void {
                PrivateMedia::delete($path);
                DB::table($table)->where($idColumn, $row->{$idColumn})->update([$pathColumn => null]);
                MediaRetentionEvent::create([
                    'media_type' => $type,
                    'record_table' => $table,
                    'record_id' => $row->{$idColumn},
                    'path_hash' => hash('sha256', $path),
                    'retention_days' => $days,
                    'action' => 'deleted',
                    'executed_at' => now(),
                ]);
            });
        });

        return $count;
    }

    private function inactivePersonPhotos(): Builder
    {
        $days = config('media_retention.personal_photo_days_after_inactivity');

        return DB::table('personas as p')
            ->whereNotNull('p.foto_path')
            ->where('p.updated_at', '<=', now()->subDays($days))
            ->whereNotExists(fn ($query) => $query->selectRaw('1')->from('socios as s')
                ->whereColumn('s.id_persona', 'p.id_persona')->where('s.estado', 'activo'))
            ->whereNotExists(fn ($query) => $query->selectRaw('1')->from('empleados as e')
                ->whereColumn('e.id_persona', 'p.id_persona')->where('e.estado', 'activo'))
            ->select(['p.id_persona', 'p.foto_path']);
    }

    private function closedTechnicalEvidence(string $table, string $idColumn, string $closedStatus): Builder
    {
        $days = config('media_retention.technical_evidence_days_after_closure');

        return DB::table($table)
            ->whereNotNull('evidencia_path')
            ->where('estado', $closedStatus)
            ->where('updated_at', '<=', now()->subDays($days))
            ->select([$idColumn, 'evidencia_path']);
    }

    private function paymentProofs(array $statuses, int $days): Builder
    {
        return DB::table('ordenes_pago')
            ->whereNotNull('comprobante_path')
            ->whereIn('estado', $statuses)
            ->where('updated_at', '<=', now()->subDays($days))
            ->select(['id_orden_pago', 'comprobante_path']);
    }
}
