<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notificacion extends Model
{
    use HasFactory;

    protected $table = 'notificaciones';
    protected $primaryKey = 'id_notificacion';

    protected $fillable = [
        'tipo',
        'mensaje',
        'fecha_envio',
        'enviado',
        'canal',
        'id_socio',
        'id_factura',
    ];

    protected $casts = [
        'fecha_envio' => 'datetime',
        'enviado'     => 'boolean',
        'creado_en'   => 'datetime',
    ];

    // ─── Relaciones ───────────────────────────────────────────────

    public function socio()
    {
        return $this->belongsTo(Socio::class, 'id_socio');
    }

    public function factura()
    {
        return $this->belongsTo(Factura::class, 'id_factura');
    }

    // ─── Scopes ───────────────────────────────────────────────────

    public function scopeEnviadas($query)
    {
        return $query->where('enviado', true);
    }

    public function scopePendientes($query)
    {
        return $query->where('enviado', false);
    }

    public function scopePorCanal($query, string $canal)
    {
        return $query->where('canal', $canal);
    }

    public function scopePorTipo($query, string $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    // ─── Helpers ─────────────────────────────────────────────────

    public function marcarComoEnviada(): void
    {
        $this->update([
            'enviado'     => true,
            'fecha_envio' => now(),
        ]);
    }
}