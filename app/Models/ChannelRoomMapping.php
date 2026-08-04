<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ChannelRoomMapping extends Model
{
    use HasUuids;

    protected $primaryKey = 'uuid';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'channel_credential_id',
        'internal_room_type_id',
        'external_room_id',
    ];

    public function channelCredential(): BelongsTo
    {
        return $this->belongsTo(ChannelCredential::class, 'channel_credential_id', 'uuid');
    }

    public function internalRoomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class, 'internal_room_type_id', 'uuid');
    }

    public function channelRateMappings(): HasMany
    {
        return $this->hasMany(ChannelRateMapping::class, 'channel_room_mapping_id', 'uuid');
    }
}
