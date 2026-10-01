<?php

namespace App\Services;

use App\Models\Cobro;
use App\Models\Empleado;
use App\Models\Factura;
use App\Models\HistorialPago;
use App\Models\MetodoPago;
use App\Models\OrdenPago;
use App\Models\OrdenTecnica;
use App\Models\SystemSetting;
use App\Support\OperationalCache;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PaymentOrderService
{
    public function __construct(private BankPaymentConfirmationPolicy $bankConfirmationPolicy)
    {
    }

    public function createFromFacturas(object $socio, array $facturaIds): OrdenPago
    {
        $this->ensureStaticQrConfigured();

        $ids = collect($facturaIds)
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            throw new \RuntimeException('Selecciona al menos un concepto pendiente para generar la orden.');
        }

        return DB::transaction(function () use ($socio, $ids) {
            $this->lockSocioOrders((int) $socio->id_socio);
            $items = $this->sequentialFacturaItemsForSelection((int) $socio->id_socio, $ids->all());

            return $this->createOrderFromItems((int) $socio->id_socio, $items);
        });
    }

    public function createSequentialUntilFactura(object $socio, int $hastaFacturaId): OrdenPago
    {
        $this->ensureStaticQrConfigured();

        return DB::transaction(function () use ($socio, $hastaFacturaId) {
            $this->lockSocioOrders((int) $socio->id_socio);
            $items = $this->sequentialFacturaItemsUntil((int) $socio->id_socio, $hastaFacturaId);

            return $this->createOrderFromItems((int) $socio->id_socio, $items);
        });
    }

    private function createOrderFromItems(int $idSocio, Collection $items): OrdenPago
    {
        $facturaIds = $items->pluck('id_factura')->map(fn ($id) => (int) $id)->all();
        $hasActiveOrder = DB::table('orden_pago_detalles as detalle')
            ->join('ordenes_pago as orden', 'orden.id_orden_pago', '=', 'detalle.id_orden_pago')
            ->where('detalle.tipo', 'factura')
            ->whereIn('detalle.referencia_id', $facturaIds)
            ->whereIn('orden.estado', ['pendiente', 'en_revision'])
            ->exists();

        if ($hasActiveOrder) {
            throw new \RuntimeException('Una de las facturas seleccionadas ya pertenece a una orden de pago activa.');
        }

        $total = round((float) $items->sum('saldo'), 2);

        if ($total <= 0) {
            throw new \RuntimeException('Los conceptos seleccionados no tienen saldo pendiente.');
        }

        $orden = OrdenPago::create([
            'codigo' => $this->nextCode(),
            'id_socio' => $idSocio,
            'total' => $total,
            'estado' => 'pendiente',
            'metodo' => 'qr_estatico',
            'access_token' => Str::random(48),
            'fecha_vencimiento' => now()->addDay(),
        ]);

        foreach ($items as $item) {
            $orden->detalles()->create([
                'tipo' => 'factura',
                'referencia_id' => $item->id_factura,
                'descripcion' => $this->descriptionForFactura($item),
                'monto' => $item->saldo,
                'metadata' => [
                    'numero_factura' => $item->numero_factura,
                    'periodo' => $item->periodo_nombre,
                    'estado_pago' => $item->estado_pago,
                    'incluye_mora' => (float) $item->recargo_mora > 0,
                ],
            ]);
        }

        $this->flushCaches();

        return $orden->fresh(['socio.persona', 'detalles']);
    }

    public function uploadProof(OrdenPago $orden, array $data, UploadedFile $file): OrdenPago
    {
        if (! in_array($orden->estado, ['pendiente', 'rechazada'], true)) {
            throw new \RuntimeException('Esta orden ya no acepta comprobantes.');
        }

        if ($orden->fecha_vencimiento?->isPast()) {
            throw new \RuntimeException('La orden de pago vencio. Genera una nueva orden antes de enviar el comprobante.');
        }

        $path = $file->storeAs(
            'comprobantes_qr',
            $orden->codigo.'_'.now()->format('YmdHis').'_'.Str::random(8).'.'.strtolower($file->extension() ?: 'jpg'),
            'local'
        );

        try {
            $orden = DB::transaction(function () use ($orden, $data, $path) {
                $orden = OrdenPago::query()->lockForUpdate()->findOrFail($orden->id_orden_pago);

                if (! in_array($orden->estado, ['pendiente', 'rechazada'], true)) {
                    throw new \RuntimeException('Esta orden ya no acepta comprobantes.');
                }

                if ($orden->fecha_vencimiento?->isPast()) {
                    throw new \RuntimeException('La orden de pago vencio. Genera una nueva orden antes de enviar el comprobante.');
                }

                $reference = trim((string) $data['comprobante_referencia']);
                $referenceAlreadyUsed = OrdenPago::query()
                    ->where('id_orden_pago', '<>', $orden->id_orden_pago)
                    ->whereRaw('LOWER(comprobante_referencia) = ?', [mb_strtolower($reference)])
                    ->whereIn('estado', ['en_revision', 'aprobada'])
                    ->exists();

                if ($referenceAlreadyUsed) {
                    throw new \RuntimeException('Esta referencia bancaria ya fue usada en otra orden. Verifica el comprobante antes de reenviarlo.');
                }

                $orden->update([
                    'estado' => 'en_revision',
                    'comprobante_path' => $path,
                    'comprobante_referencia' => $reference,
                    'entidad_financiera' => trim((string) $data['entidad_financiera']),
                    'comprobante_monto' => round((float) $orden->total, 2),
                    'comprobante_fecha' => now()->toDateString(),
                    'observaciones_cliente' => $data['observaciones_cliente'] ?? null,
                    'notas_revision' => null,
                    'revisado_por' => null,
                    'revisado_en' => null,
                ]);

                return $orden;
            }, 3);
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }

        $this->flushCaches();

        return $orden->fresh(['socio.persona', 'detalles']);
    }

    public function approve(OrdenPago $orden, ?string $notes = null): array
    {
        return $this->approveWithEmployee($orden, $notes, $this->resolveEmpleado());
    }

    public function approveAutomatically(OrdenPago $orden, array $payment, string $provider): array
    {
        if ($orden->estado === 'aprobada') {
            return [
                'cobros' => $orden->cobros()->get(),
                'technical_order' => null,
                'orden' => $orden->fresh(['socio.persona', 'detalles']),
            ];
        }

        $orden = DB::transaction(function () use ($orden, $payment, $provider): OrdenPago {
            $orden = OrdenPago::query()->lockForUpdate()->findOrFail($orden->id_orden_pago);

            if ($orden->estado === 'aprobada') {
                return $orden;
            }

            $this->bankConfirmationPolicy->validate($orden, $payment);

            $reference = trim((string) $payment['reference']);
            $referenceAlreadyUsed = OrdenPago::query()
                ->where('id_orden_pago', '<>', $orden->id_orden_pago)
                ->whereRaw('LOWER(comprobante_referencia) = ?', [mb_strtolower($reference)])
                ->where('estado', 'aprobada')
                ->exists();

            if ($referenceAlreadyUsed) {
                throw new \RuntimeException('La referencia bancaria ya fue aplicada a otra orden.');
            }

            $orden->update([
                'estado' => 'en_revision',
                'metodo' => 'qr_bancario',
                'comprobante_referencia' => $reference,
                'entidad_financiera' => $provider,
                'comprobante_monto' => round((float) $payment['amount'], 2),
                'comprobante_fecha' => Carbon::parse($payment['paid_at'])->toDateString(),
                'notas_revision' => 'Confirmacion bancaria automatica.',
            ]);

            return $orden;
        }, 3);

        if ($orden->estado === 'aprobada') {
            return [
                'cobros' => $orden->cobros()->get(),
                'technical_order' => null,
                'orden' => $orden->fresh(['socio.persona', 'detalles']),
            ];
        }

        return $this->approveWithEmployee(
            $orden,
            'Confirmado automaticamente por '.$provider.'.',
            $this->automaticEmployee(),
            'pago_qr_confirmado_banco',
            "Pago QR confirmado automaticamente por {$provider} mediante orden {$orden->codigo}."
        );
    }

    private function approveWithEmployee(
        OrdenPago $orden,
        ?string $notes,
        Empleado $empleado,
        string $historyType = 'pago_qr_aprobado',
        ?string $historyDescription = null
    ): array {
        if ($orden->estado !== 'en_revision') {
            throw new \RuntimeException('Solo se pueden aprobar ordenes en revision.');
        }

        $result = DB::transaction(function () use ($orden, $notes, $empleado, $historyType, $historyDescription) {
            $orden = OrdenPago::query()
                ->with(['detalles', 'socio.persona'])
                ->lockForUpdate()
                ->findOrFail($orden->id_orden_pago);

            if ($orden->estado !== 'en_revision') {
                throw new \RuntimeException('Esta orden ya fue revisada por otro usuario.');
            }

            $facturaIds = $orden->detalles
                ->where('tipo', 'factura')
                ->pluck('referencia_id')
                ->map(fn ($id) => (int) $id)
                ->values();

            $items = $this->sequentialFacturaItemsForSelection((int) $orden->id_socio, $facturaIds->all(), true);

            foreach ($orden->detalles as $detail) {
                if ($detail->tipo !== 'factura') {
                    continue;
                }

                $item = $items->firstWhere('id_factura', (int) $detail->referencia_id);

                if (! $item || abs((float) $item->saldo - (float) $detail->monto) > 0.009) {
                    throw new \RuntimeException('La deuda cambio desde que se creo la orden. Rechaza esta orden y genera una nueva.');
                }
            }

            $method = $this->qrPaymentMethod();
            $createdCobros = collect();
            $fechaPago = now()->toDateString();

            foreach ($orden->detalles as $detail) {
                if ($detail->tipo !== 'factura') {
                    continue;
                }

                $item = $items->firstWhere('id_factura', (int) $detail->referencia_id);
                $factura = Factura::query()
                    ->lockForUpdate()
                    ->findOrFail($item->id_factura);

                $cobro = Cobro::create($this->cobroPayload([
                    'fecha_cobro' => $fechaPago,
                    'monto_pagado' => (float) $detail->monto,
                    'monto_pendiente' => 0,
                    'estado' => 'completado',
                    'comprobante' => $orden->comprobante_referencia ?: $orden->codigo,
                    'id_factura' => $factura->id_factura,
                    'id_metodo_pago' => $method->id_metodo_pago,
                    'id_empleado' => $empleado->id_empleado,
                    'id_orden_pago' => $orden->id_orden_pago,
                ], [
                    'origen_pago' => 'online',
                    'id_cierre_caja' => null,
                    'referencia_externa' => $orden->comprobante_referencia ?: $orden->codigo,
                    'estado_conciliacion' => 'conciliado',
                    'confirmado_por' => 'pasarela_qr',
                    'confirmado_en' => now(),
                ]));

                $factura->update([
                    'estado' => 'pagada',
                    'fecha_pago' => $fechaPago,
                ]);

                HistorialPago::create([
                    'fecha_evento' => now(),
                    'tipo_evento' => $historyType,
                    'descripcion' => $historyDescription ?: "Pago QR aprobado mediante orden {$orden->codigo}.",
                    'monto' => (float) $detail->monto,
                    'id_socio' => $factura->id_socio,
                    'id_factura' => $factura->id_factura,
                    'id_cobro' => $cobro->id_cobro,
                    'id_empleado' => $empleado->id_empleado,
                ]);

                $createdCobros->push($cobro);
            }

            $orden->update([
                'estado' => 'aprobada',
                'notas_revision' => $notes,
                'revisado_por' => $empleado->id_empleado,
                'revisado_en' => now(),
            ]);

            $technicalOrder = $this->createReconnectionRequestIfNeeded($orden, $empleado);
            $this->flushCaches();

            return [
                'cobros' => $createdCobros,
                'technical_order' => $technicalOrder,
                'orden' => $orden->fresh(['socio.persona', 'detalles']),
            ];
        });

        return $result;
    }

    public function reject(OrdenPago $orden, string $notes): OrdenPago
    {
        if (! in_array($orden->estado, ['en_revision', 'pendiente'], true)) {
            throw new \RuntimeException('Esta orden ya no puede rechazarse.');
        }

        $empleado = $this->resolveEmpleado();
        $orden = DB::transaction(function () use ($orden, $notes, $empleado) {
            $orden = OrdenPago::query()->lockForUpdate()->findOrFail($orden->id_orden_pago);

            if (! in_array($orden->estado, ['en_revision', 'pendiente'], true)) {
                throw new \RuntimeException('Esta orden ya fue revisada por otro usuario.');
            }

            $orden->update([
                'estado' => 'rechazada',
                'notas_revision' => $notes,
                'revisado_por' => $empleado->id_empleado,
                'revisado_en' => now(),
            ]);

            return $orden;
        }, 3);

        $this->flushCaches();

        return $orden->fresh(['socio.persona', 'detalles']);
    }

    public function staticQrSvg(?OrdenPago $orden = null): string
    {
        $basePayload = $this->staticQrPayload();

        if ($basePayload === null) {
            return '';
        }

        $payload = $orden
            ? json_encode([
                'merchant' => $basePayload,
                'order' => $orden->codigo,
                'amount' => number_format((float) $orden->total, 2, '.', ''),
                'currency' => 'BOB',
                'expires_at' => $orden->fecha_vencimiento?->toIso8601String(),
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            : $basePayload;

        return (new Writer(
            new ImageRenderer(new RendererStyle(300), new SvgImageBackEnd)
        ))->writeString($payload);
    }

    private function ensureStaticQrConfigured(): void
    {
        if ($this->staticQrPayload() === null) {
            throw new \RuntimeException('El QR oficial de pago no esta configurado. Completa la pasarela bancaria antes de generar ordenes QR.');
        }
    }

    private function staticQrPayload(): ?string
    {
        $settings = SystemSetting::getValue('general', []);
        $payload = trim((string) ($settings['payment_static_qr_payload'] ?? ''));

        return $payload !== '' ? $payload : null;
    }

    public function financialEntities(): array
    {
        return [
            'Banco Union',
            'Banco Nacional de Bolivia (BNB)',
            'Banco Mercantil Santa Cruz',
            'Banco BISA',
            'Banco de Credito BCP',
            'Banco Ganadero',
            'Banco Economico',
            'Banco FIE',
            'BancoSol',
            'Banco Fortaleza',
            'Banco Prodem',
            'Banco PyME Ecofuturo',
            'Banco de Desarrollo Productivo (BDP)',
            'Cooperativa / QR interoperable',
            'Yape / billetera movil',
            'Otro',
        ];
    }

    public function pendingFacturaItems(int $idSocio, array $facturaIds, bool $lock = false): Collection
    {
        $ids = collect($facturaIds)
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        return $this->pendingFacturaItemsForSocio($idSocio, $lock)
            ->whereIn('id_factura', $ids->all())
            ->values();
    }

    private function pendingFacturaItemsForSocio(int $idSocio, bool $lock = false): Collection
    {
        if ($lock) {
            DB::table('facturas')
                ->where('id_socio', $idSocio)
                ->where('estado', '!=', 'anulada')
                ->orderBy('id_factura')
                ->lockForUpdate()
                ->pluck('id_factura');
        }

        $payments = DB::table('cobros')
            ->selectRaw("id_factura, COALESCE(SUM(CASE WHEN estado <> 'anulado' THEN monto_pagado ELSE 0 END), 0) as pagado")
            ->groupBy('id_factura');

        $query = DB::table('facturas as f')
            ->leftJoin('periodos_facturacion as pf', 'pf.id_periodo', '=', 'f.id_periodo')
            ->leftJoinSub($payments, 'cp', fn ($join) => $join->on('cp.id_factura', '=', 'f.id_factura'))
            ->where('f.id_socio', $idSocio)
            ->where('f.estado', '!=', 'anulada')
            ->select([
                'f.id_factura',
                'f.numero_factura',
                'f.fecha_emision',
                'f.fecha_inicio_cobro',
                'f.fecha_fin_cobro',
                'f.total',
                'f.recargo_mora',
                'f.estado',
                'pf.nombre as periodo_nombre',
                'pf.fecha_inicio as periodo_inicio',
                'pf.fecha_fin as periodo_fin',
            ])
            ->selectRaw('COALESCE(cp.pagado, 0) as pagado')
            ->selectRaw('ROUND(GREATEST(f.total - COALESCE(cp.pagado, 0), 0), 2) as saldo')
            ->selectRaw("CASE WHEN GREATEST(f.total - COALESCE(cp.pagado, 0), 0) > 0 AND COALESCE(f.fecha_fin_cobro, pf.fecha_fin, f.fecha_emision) + INTERVAL '30 days' < CURRENT_DATE THEN 'Vencida' ELSE 'Pendiente' END as estado_pago")
            ->selectRaw('COALESCE(f.fecha_fin_cobro, pf.fecha_fin, f.fecha_emision) as fecha_orden')
            ->orderByRaw('COALESCE(f.fecha_fin_cobro, pf.fecha_fin, f.fecha_emision) ASC')
            ->orderBy('f.id_factura');

        return $query->get()->filter(fn ($item) => (float) $item->saldo > 0)->values();
    }

    private function sequentialFacturaItemsUntil(int $idSocio, int $hastaFacturaId, bool $lock = false): Collection
    {
        return $this->sequentialFacturaItemsForSelection($idSocio, [$hastaFacturaId], $lock, true);
    }

    private function sequentialFacturaItemsForSelection(int $idSocio, array $facturaIds, bool $lock = false, bool $selectUntil = false): Collection
    {
        $selectedIds = collect($facturaIds)
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($selectedIds->isEmpty()) {
            throw new \RuntimeException('Selecciona al menos una factura pendiente para generar la orden.');
        }

        $pending = $this->pendingFacturaItemsForSocio($idSocio, $lock);

        if ($pending->isEmpty()) {
            throw new \RuntimeException('El socio no tiene facturas pendientes para pagar.');
        }

        $highestIndex = null;
        $selectedLookup = array_flip($selectedIds->all());

        foreach ($pending as $index => $item) {
            if (isset($selectedLookup[(int) $item->id_factura])) {
                $highestIndex = $index;
            }
        }

        if ($highestIndex === null) {
            throw new \RuntimeException('La factura seleccionada ya fue pagada o no pertenece al socio.');
        }

        $expected = $pending
            ->take($highestIndex + 1)
            ->pluck('id_factura')
            ->map(fn ($id) => (int) $id)
            ->values();

        if (! $selectUntil) {
            $missingOlder = $expected->diff($selectedIds)->values();
            $extraSelected = $selectedIds->diff($expected)->values();

            if ($missingOlder->isNotEmpty() || $extraSelected->isNotEmpty()) {
                throw new \RuntimeException('Debe pagar las facturas en orden cronologico. No puedes cancelar meses recientes dejando meses antiguos pendientes.');
            }
        }

        return $pending->take($highestIndex + 1)->values();
    }

    private function descriptionForFactura(object $item): string
    {
        $parts = ['Factura '.$item->numero_factura];

        if ($item->periodo_nombre) {
            $parts[] = $item->periodo_nombre;
        }

        if ((float) $item->recargo_mora > 0) {
            $parts[] = 'incluye mora/multa';
        }

        return implode(' - ', $parts);
    }

    private function nextCode(): string
    {
        do {
            $code = 'OP-'.now()->format('Ymd').'-'.Str::upper(Str::random(8));
        } while (OrdenPago::where('codigo', $code)->exists());

        return $code;
    }

    private function lockSocioOrders(int $idSocio): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::select('SELECT pg_advisory_xact_lock(?)', [$idSocio]);

            return;
        }

        DB::table('socios')->where('id_socio', $idSocio)->lockForUpdate()->value('id_socio');
    }

    private function qrPaymentMethod(): MetodoPago
    {
        return Cache::remember('payment-order:qr-online-method', now()->addHours(12), function () {
            return MetodoPago::query()
                ->whereRaw('LOWER(nombre) = ?', ['qr online'])
                ->first()
                ?: MetodoPago::create([
                    'nombre' => 'QR online',
                    'descripcion' => 'Pago QR verificado mediante orden de pago del portal.',
                    'requiere_referencia' => true,
                    'requiere_caja_abierta' => false,
                    'es_online' => true,
                    'requiere_conciliacion' => true,
                    'origen_predeterminado' => 'online',
                    'estado' => 'activo',
                ]);
        });
    }

    private function cobroPayload(array $base, array $operational): array
    {
        foreach ($operational as $column => $value) {
            if ($this->cobrosHasColumn($column)) {
                $base[$column] = $value;
            }
        }

        return $base;
    }

    private function cobrosHasColumn(string $column): bool
    {
        return Cache::remember("schema:cobros:{$column}", now()->addHours(12), fn () => Schema::hasColumn('cobros', $column));
    }

    private function resolveEmpleado(): Empleado
    {
        $user = Auth::user();

        if ($user?->email) {
            $empleado = Cache::remember('payment-order:employee-by-email:'.md5($user->email), now()->addMinutes(30), function () use ($user) {
                return Empleado::query()
                    ->where('estado', 'activo')
                    ->whereHas('persona', fn ($persona) => $persona->where('email', $user->email))
                    ->first();
            });

            if ($empleado) {
                return $empleado;
            }
        }

        return Cache::remember('payment-order:fallback-employee', now()->addMinutes(30), function () {
            return Empleado::query()->where('estado', 'activo')->orderBy('id_empleado')->firstOrFail();
        });
    }

    private function automaticEmployee(): Empleado
    {
        $configuredId = (int) config('services.bank_webhook.employee_id');

        if ($configuredId <= 0) {
            throw new \RuntimeException('Configura el empleado de sistema para confirmar pagos bancarios.');
        }

        return Cache::remember('payment-order:automatic-employee:'.$configuredId, now()->addHours(12), function () use ($configuredId) {
            return Empleado::query()
                ->where('estado', 'activo')
                ->where('id_empleado', $configuredId)
                ->firstOrFail();
        });
    }

    private function createReconnectionRequestIfNeeded(OrdenPago $orden, Empleado $empleado): ?OrdenTecnica
    {
        $socio = $orden->socio()->with(['medidorActivo', 'sector'])->first();

        if (! $socio || $socio->estado !== 'cortado' || $this->remainingDebt((int) $socio->id_socio) > 0) {
            return null;
        }

        $existing = OrdenTecnica::query()
            ->where('tipo', 'reconexion')
            ->where('id_socio', $socio->id_socio)
            ->whereIn('estado', ['pendiente', 'aprobada', 'en_proceso'])
            ->first();

        if ($existing) {
            return $existing;
        }

        return OrdenTecnica::create([
            'tipo' => 'reconexion',
            'estado' => 'aprobada',
            'prioridad' => 'alta',
            'fecha_programada' => now()->addDay()->toDateString(),
            'zona' => $socio->sector?->nombre,
            'referencia' => 'Orden de pago '.$orden->codigo,
            'descripcion' => 'Reconexión generada automáticamente tras aprobación de pago QR y cancelación de deuda.',
            'id_socio' => $socio->id_socio,
            'id_medidor' => $socio->medidorActivo?->id_medidor,
            'id_empleado' => $empleado->id_empleado,
        ]);
    }

    private function remainingDebt(int $idSocio): float
    {
        $payments = DB::table('cobros')
            ->selectRaw("id_factura, COALESCE(SUM(CASE WHEN estado <> 'anulado' THEN monto_pagado ELSE 0 END), 0) as pagado")
            ->groupBy('id_factura');

        return (float) DB::table('facturas as f')
            ->leftJoinSub($payments, 'cp', fn ($join) => $join->on('cp.id_factura', '=', 'f.id_factura'))
            ->where('f.id_socio', $idSocio)
            ->where('f.estado', '!=', 'anulada')
            ->selectRaw('ROUND(COALESCE(SUM(GREATEST(f.total - COALESCE(cp.pagado, 0), 0)), 0), 2) as saldo')
            ->value('saldo');
    }

    private function flushCaches(): void
    {
        OperationalCache::bumpDomain('billing');
        Cache::forget('payment-orders:index:stats');
        Cache::add('payment-orders:index:version', 1, now()->addYears(2));
        Cache::increment('payment-orders:index:version');
        Cache::forget('dashboard.secretaria.stats');
        Cache::forget('dashboard.secretaria.payment-orders.pending');
        Cache::forget('dashboard.secretaria.payments.recent');
        Cache::forget('api.dashboard.secretaria');
        Cache::forget('facturas.totales');
        Cache::forget('facturas.billing_candidates');
        Cache::forget('tecnico:billing-signals');
        Cache::forget('tecnico:corte:open-socios');
        Cache::forget('tecnico:reconexion:open-socios');
        Cache::forget('tecnico:reconexion:latest-cuts');
        Cache::forget('api.dashboard.tecnico');
        OperationalCache::forget('billing:signals');
        OperationalCache::forget('corte:open-socios');
        OperationalCache::forget('reconexion:open-socios');
        OperationalCache::forget('reconexion:latest-cuts');
        OperationalCache::forget('orders:reconexion:summary');
        OperationalCache::forget('api-dashboard-tecnico');
    }
}
