<?php

namespace App\Services;

use App\Models\Factura;
use App\Models\Notificacion;
use App\Models\Socio;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NotificacionService
{
    /**
     * Canales disponibles definidos en la BD:
     * sms | email | whatsapp | sistema
     */
    private const CANALES_VALIDOS = ['sms', 'email', 'whatsapp', 'sistema'];

    /**
     * Tipos de notificación del sistema:
     * factura_emitida | factura_vencida | pago_recibido | corte_servicio | aviso_mora
     */

    // ──────────────────────────────────────────
    //  Consultas
    // ──────────────────────────────────────────

    public function listar(array $filtros = []): Collection
    {
        $query = Notificacion::with(['socio.persona', 'factura'])
            ->orderByDesc('fecha_envio');

        if (!empty($filtros['id_socio'])) {
            $query->where('id_socio', $filtros['id_socio']);
        }
        if (!empty($filtros['canal'])) {
            $query->where('canal', $filtros['canal']);
        }
        if (isset($filtros['enviado'])) {
            $query->where('enviado', (bool) $filtros['enviado']);
        }

        return $query->get();
    }

    public function pendientes(): Collection
    {
        return Notificacion::with(['socio.persona', 'factura'])
            ->pendientes()
            ->orderBy('fecha_envio')
            ->get();
    }

    // ──────────────────────────────────────────
    //  Creación de notificaciones
    // ──────────────────────────────────────────

    /**
     * Crea una notificación manualmente para un socio.
     *
     * @param array{
     *   id_socio: int,
     *   tipo: string,
     *   mensaje: string,
     *   canal: string,
     *   id_factura?: int
     * } $data
     */
    public function crear(array $data): Notificacion
    {
        if (!in_array($data['canal'], self::CANALES_VALIDOS)) {
            throw new \InvalidArgumentException(
                "Canal '{$data['canal']}' no válido. Use: " . implode(', ', self::CANALES_VALIDOS)
            );
        }

        return Notificacion::create([
            'tipo'       => $data['tipo'],
            'mensaje'    => $data['mensaje'],
            'canal'      => $data['canal'],
            'enviado'    => false,
            'id_socio'   => $data['id_socio'],
            'id_factura' => $data['id_factura'] ?? null,
        ]);
    }

    /**
     * Notifica al socio sobre la emisión de su factura.
     * Crea notificaciones en todos los canales disponibles del socio.
     */
    public function notificarFacturaEmitida(Factura $factura): array
    {
        $factura->loadMissing(['socio.persona', 'periodo']);
        $socio   = $factura->socio;
        $persona = $socio->persona;

        $mensaje = "Estimado/a {$persona->nombres}, su factura #{$factura->numero_factura} "
                 . "del período '{$factura->periodo->nombre}' por Bs. {$factura->total} "
                 . "está disponible. Fecha límite de pago próxima.";

        return $this->enviarPorCanalesDisponibles($socio, $factura, 'factura_emitida', $mensaje);
    }

    /**
     * Notifica a todos los socios con facturas vencidas de un período.
     * Ideal para ejecutar desde un Comando/Scheduler.
     */
    public function notificarFacturasVencidas(int $idPeriodo): array
    {
        $facturasVencidas = Factura::with(['socio.persona', 'periodo'])
            ->where('id_periodo', $idPeriodo)
            ->where('estado', 'vencida')
            ->get();

        $enviadas = 0;
        $errores  = [];

        foreach ($facturasVencidas as $factura) {
            try {
                $persona = $factura->socio->persona;
                $mensaje = "Estimado/a {$persona->nombres}, su factura #{$factura->numero_factura} "
                         . "de Bs. {$factura->total} está VENCIDA. "
                         . "Comuníquese con nuestra oficina para regularizar su deuda.";

                $this->enviarPorCanalesDisponibles(
                    $factura->socio,
                    $factura,
                    'factura_vencida',
                    $mensaje
                );
                $enviadas++;
            } catch (\Exception $e) {
                $errores[] = [
                    'id_factura' => $factura->id_factura,
                    'motivo'     => $e->getMessage(),
                ];
            }
        }

        return [
            'enviadas' => $enviadas,
            'errores'  => $errores,
        ];
    }

    /**
     * Notifica confirmación de pago al socio.
     */
    public function notificarPagoRecibido(Factura $factura, float $montoPagado): array
    {
        $factura->loadMissing('socio.persona');
        $persona = $factura->socio->persona;

        $mensaje = "Estimado/a {$persona->nombres}, recibimos su pago de Bs. {$montoPagado} "
                 . "para la factura #{$factura->numero_factura}. ¡Gracias!";

        return $this->enviarPorCanalesDisponibles(
            $factura->socio,
            $factura,
            'pago_recibido',
            $mensaje
        );
    }

    /**
     * Notifica al socio sobre el corte de su servicio.
     */
    public function notificarCorteServicio(Socio $socio): Notificacion
    {
        $socio->loadMissing('persona');
        $persona = $socio->persona;

        $mensaje = "Estimado/a {$persona->nombres}, su servicio de agua ha sido "
                 . "SUSPENDIDO por falta de pago. Comuníquese con nuestra oficina.";

        return $this->crear([
            'id_socio' => $socio->id_socio,
            'tipo'     => 'corte_servicio',
            'mensaje'  => $mensaje,
            'canal'    => 'sistema',
        ]);
    }

    // ──────────────────────────────────────────
    //  Envío real (despacho)
    // ──────────────────────────────────────────

    /**
     * Marca una notificación como enviada.
     * Aquí se conectaría con el driver real (Twilio, Mailgun, etc.).
     */
    public function marcarComoEnviada(int $idNotificacion): Notificacion
    {
        $notificacion = Notificacion::findOrFail($idNotificacion);

        if ($notificacion->enviado) {
            return $notificacion;
        }

        $notificacion->update([
            'enviado'     => true,
            'fecha_envio' => now(),
        ]);

        return $notificacion->fresh();
    }

    /**
     * Procesa y despacha todas las notificaciones pendientes.
     * Se recomienda ejecutar desde un Comando/Scheduler.
     *
     * @return array{procesadas: int, fallidas: int}
     */
    public function despacharPendientes(): array
    {
        $pendientes = $this->pendientes();
        $procesadas = 0;
        $fallidas   = 0;

        foreach ($pendientes as $notificacion) {
            try {
                // Aquí iría la integración real con el canal (SMS, email, etc.)
                // Por ejemplo: TwilioClient::send(...), Mail::to(...)->send(...)
                $this->despacharPorCanal($notificacion);

                $notificacion->update([
                    'enviado'     => true,
                    'fecha_envio' => now(),
                ]);
                $procesadas++;
            } catch (\Exception $e) {
                Log::error("Error enviando notificación #{$notificacion->id_notificacion}: {$e->getMessage()}");
                $fallidas++;
            }
        }

        return [
            'procesadas' => $procesadas,
            'fallidas'   => $fallidas,
        ];
    }

    // ──────────────────────────────────────────
    //  Helpers privados
    // ──────────────────────────────────────────

    /**
     * Determina los canales disponibles para el socio y crea una notificación
     * por cada canal válido.
     *
     * @return Notificacion[]
     */
    private function enviarPorCanalesDisponibles(
        Socio $socio,
        ?Factura $factura,
        string $tipo,
        string $mensaje
    ): array {
        $socio->loadMissing('persona');
        $persona  = $socio->persona;
        $creadas  = [];

        // Canal sistema: siempre disponible
        $creadas[] = $this->crear([
            'id_socio'   => $socio->id_socio,
            'tipo'       => $tipo,
            'mensaje'    => $mensaje,
            'canal'      => 'sistema',
            'id_factura' => $factura?->id_factura,
        ]);

        // SMS / WhatsApp: solo si el socio tiene teléfono
        if (!empty($persona->telefono)) {
            $creadas[] = $this->crear([
                'id_socio'   => $socio->id_socio,
                'tipo'       => $tipo,
                'mensaje'    => $mensaje,
                'canal'      => 'sms',
                'id_factura' => $factura?->id_factura,
            ]);
        }

        // Email: solo si el socio tiene correo registrado
        if (!empty($persona->email)) {
            $creadas[] = $this->crear([
                'id_socio'   => $socio->id_socio,
                'tipo'       => $tipo,
                'mensaje'    => $mensaje,
                'canal'      => 'email',
                'id_factura' => $factura?->id_factura,
            ]);
        }

        return $creadas;
    }

    /**
     * Stub para el despacho real por canal.
     * Reemplazar con el cliente correspondiente en producción.
     */
    private function despacharPorCanal(Notificacion $notificacion): void
    {
        match ($notificacion->canal) {
            'sms'      => $this->enviarSms($notificacion),
            'email'    => $this->enviarEmail($notificacion),
            'whatsapp' => $this->enviarWhatsapp($notificacion),
            'sistema'  => null, // solo se almacena en BD
            default    => throw new \RuntimeException("Canal desconocido: {$notificacion->canal}"),
        };
    }

    /** Integrar con Twilio, Vonage, etc. */
    private function enviarSms(Notificacion $notificacion): void
    {
        // TODO: TwilioClient::sendSms($notificacion->socio->persona->telefono, $notificacion->mensaje);
        Log::info("[SMS] Para: {$notificacion->socio->persona->telefono} | {$notificacion->mensaje}");
    }

    /** Integrar con Mailgun, SES, SMTP, etc. */
    private function enviarEmail(Notificacion $notificacion): void
    {
        // TODO: Mail::to($notificacion->socio->persona->email)->send(new FacturaMailable($notificacion));
        Log::info("[EMAIL] Para: {$notificacion->socio->persona->email} | {$notificacion->mensaje}");
    }

    /** Integrar con Twilio WhatsApp o Meta Cloud API. */
    private function enviarWhatsapp(Notificacion $notificacion): void
    {
        // TODO: WhatsappClient::send($notificacion->socio->persona->telefono, $notificacion->mensaje);
        Log::info("[WHATSAPP] Para: {$notificacion->socio->persona->telefono} | {$notificacion->mensaje}");
    }
}