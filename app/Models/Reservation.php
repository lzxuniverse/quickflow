<?php

namespace App\Models;

use App\Domains\Property\Models\Property;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Reservation extends Model
{
    use HasUuids;

    protected $primaryKey = 'uuid';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'property_id',
        'guest_id',
        'status',
        'source',
        'check_in_date',
        'check_out_date',
        'total_amount',
    ];

    protected $casts = [
        'check_in_date' => 'date',
        'check_out_date' => 'date',
        'total_amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class, 'property_id', 'uuid');
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class, 'guest_id', 'uuid');
    }

    public function aiConversations(): HasMany
    {
        return $this->hasMany(AiConversation::class, 'reservation_id', 'uuid');
    }

    public function folios(): HasMany
    {
        return $this->hasMany(Folio::class, 'reservation_id', 'uuid');
    }

    public function reservationGuests(): HasMany
    {
        return $this->hasMany(ReservationGuest::class, 'reservation_id', 'uuid');
    }

    public function reservationRooms(): HasMany
    {
        return $this->hasMany(ReservationRoom::class, 'reservation_id', 'uuid');
    }

    public function reservationVersions(): HasMany
    {
        return $this->hasMany(ReservationVersion::class, 'reservation_id', 'uuid');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'reservation_id', 'uuid');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForProperty($query, $propertyId)
    {
        return $query->where('property_id', $propertyId);
    }
}
