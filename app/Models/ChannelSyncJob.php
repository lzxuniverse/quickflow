<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ChannelSyncJob extends Model
{
    use HasUuids;

    protected $primaryKey = 'uuid';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'channel_credential_id',
        'sync_type',
        'status',
        'error_log',
        'latency_ms',
    ];

    protected $casts = [
        'latency_ms' => 'integer',
        'created_at' => 'datetime',
    ];

    public function channelCredential(): BelongsTo
    {
        return $this->belongsTo(ChannelCredential::class, 'channel_credential_id', 'uuid');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
