<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ChannelRateMapping extends Model
{
    use HasUuids;

    protected $primaryKey = 'uuid';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'channel_room_mapping_id',
        'internal_rate_plan_id',
        'external_rate_id',
        'markup_percent',
    ];

    protected $casts = [
        'markup_percent' => 'decimal:2',
    ];

    public function channelRoomMapping(): BelongsTo
    {
        return $this->belongsTo(ChannelRoomMapping::class, 'channel_room_mapping_id', 'uuid');
    }

    public function internalRatePlan(): BelongsTo
    {
        return $this->belongsTo(RatePlan::class, 'internal_rate_plan_id', 'uuid');
    }
}
