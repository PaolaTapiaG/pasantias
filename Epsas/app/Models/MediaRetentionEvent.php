<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MediaRetentionEvent extends Model
{
    protected $table = 'media_retention_events';
    protected $primaryKey = 'id_retention_event';

    protected $fillable = [
        'media_type',
        'record_table',
        'record_id',
        'path_hash',
        'retention_days',
        'action',
        'executed_at',
    ];

    protected $casts = [
        'executed_at' => 'datetime',
    ];
}
