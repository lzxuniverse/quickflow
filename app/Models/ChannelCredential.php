<?php

namespace App\Models;

use App\Domains\Property\Models\Property;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ChannelCredential extends Model
{
    use HasUuids;

    protected $primaryKey = 'uuid';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'property_id',
        'channel_id',
        'encrypted_auth_data',
        'connection_status',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class, 'property_id', 'uuid');
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class, 'channel_id', 'id');
    }

    public function channelRoomMappings(): HasMany
    {
        return $this->hasMany(ChannelRoomMapping::class, 'channel_credential_id', 'uuid');
    }

    public function channelSyncJobs(): HasMany
    {
        return $this->hasMany(ChannelSyncJob::class, 'channel_credential_id', 'uuid');
    }

    public function scopeForProperty($query, $propertyId)
    {
        return $query->where('property_id', $propertyId);
    }
}
