<?php

namespace App\Models;

use App\Domains\Property\Models\Property;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class RoomType extends Model
{
    use HasUuids;

    protected $primaryKey = 'uuid';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'property_id',
        'name',
        'code',
        'max_adults',
        'max_children',
        'base_rate',
        'inventory_mode',
    ];

    protected $casts = [
        'max_adults' => 'integer',
        'max_children' => 'integer',
        'base_rate' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class, 'property_id', 'uuid');
    }

    public function bedConfigurations(): HasMany
    {
        return $this->hasMany(BedConfiguration::class, 'room_type_id', 'uuid');
    }

    public function channelRoomMappings(): HasMany
    {
        return $this->hasMany(ChannelRoomMapping::class, 'internal_room_type_id', 'uuid');
    }

    public function inventoryGrids(): HasMany
    {
        return $this->hasMany(InventoryGrid::class, 'room_type_id', 'uuid');
    }

    public function ratePlans(): HasMany
    {
        return $this->hasMany(RatePlan::class, 'room_type_id', 'uuid');
    }

    public function reservationRooms(): HasMany
    {
        return $this->hasMany(ReservationRoom::class, 'room_type_id', 'uuid');
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class, 'room_type_id', 'uuid');
    }

    public function scopeForProperty($query, $propertyId)
    {
        return $query->where('property_id', $propertyId);
    }
}
