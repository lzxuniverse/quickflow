<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PmsMapping extends Model
{
    use HasUuids;

    protected $primaryKey = 'uuid';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'pms_connection_id',
        'mapping_type',
        'local_entity_id',
        'remote_entity_id',
    ];

    public function pmsConnection(): BelongsTo
    {
        return $this->belongsTo(PmsConnection::class, 'pms_connection_id', 'uuid');
    }
}
