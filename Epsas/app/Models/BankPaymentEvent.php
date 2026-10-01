<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankPaymentEvent extends Model
{
    protected $table = 'bank_payment_events';
    protected $primaryKey = 'id_event';

    protected $fillable = [
        'provider',
        'event_id',
        'event_type',
        'order_code',
        'reference',
        'amount',
        'currency',
        'status',
        'payload_hash',
        'payload',
        'processed_at',
        'error_message',
        'id_orden_pago',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payload' => 'array',
        'processed_at' => 'datetime',
    ];

    public function ordenPago(): BelongsTo
    {
        return $this->belongsTo(OrdenPago::class, 'id_orden_pago', 'id_orden_pago');
    }
}
