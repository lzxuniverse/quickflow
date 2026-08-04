<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class RatePlan extends Model
{
    use HasUuids;

    protected $primaryKey = 'uuid';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'property_id',
        'room_type_id',
        'name',
        'is_derived',
        'base_rate_plan_id',
    ];

    protected $casts = [
        'is_derived' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class, 'property_id', 'uuid');
    }

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class, 'room_type_id', 'uuid');
    }

    public function baseRatePlan(): BelongsTo
    {
        return $this->belongsTo(RatePlan::class, 'base_rate_plan_id', 'uuid');
    }

    public function channelRateMappings(): HasMany
    {
        return $this->hasMany(ChannelRateMapping::class, 'internal_rate_plan_id', 'uuid');
    }

    public function derivedRateFormulas(): HasMany
    {
        return $this->hasMany(DerivedRateFormula::class, 'target_rate_plan_id', 'uuid');
    }

    public function priceGrids(): HasMany
    {
        return $this->hasMany(PriceGrid::class, 'rate_plan_id', 'uuid');
    }

    public function ratePlans(): HasMany
    {
        return $this->hasMany(RatePlan::class, 'base_rate_plan_id', 'uuid');
    }

    public function reservationRooms(): HasMany
    {
        return $this->hasMany(ReservationRoom::class, 'rate_plan_id', 'uuid');
    }

    public function restrictionGrids(): HasMany
    {
        return $this->hasMany(RestrictionGrid::class, 'rate_plan_id', 'uuid');
    }

    public function scopeForProperty($query, $propertyId)
    {
        return $query->where('property_id', $propertyId);
    }
}
