<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PmsConnection extends Model
{
    use HasUuids;

    protected $primaryKey = 'uuid';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'property_id',
        'pms_provider',
        'pms_access_token',
        'pms_refresh_token',
        'token_expires_at',
    ];

    protected $casts = [
        'token_expires_at' => 'datetime',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class, 'property_id', 'uuid');
    }

    public function pmsMappings(): HasMany
    {
        return $this->hasMany(PmsMapping::class, 'pms_connection_id', 'uuid');
    }

    public function pmsSyncQueues(): HasMany
    {
        return $this->hasMany(PmsSyncQueue::class, 'pms_connection_id', 'uuid');
    }

    public function scopeForProperty($query, $propertyId)
    {
        return $query->where('property_id', $propertyId);
    }
}
