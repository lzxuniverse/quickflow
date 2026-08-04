<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PmsSyncQueue extends Model
{
    use HasUuids;

    protected $table = 'pms_sync_queue';

    protected $primaryKey = 'uuid';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'pms_connection_id',
        'entity_type',
        'entity_id',
        'direction',
        'sync_status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function pmsConnection(): BelongsTo
    {
        return $this->belongsTo(PmsConnection::class, 'pms_connection_id', 'uuid');
    }
}
