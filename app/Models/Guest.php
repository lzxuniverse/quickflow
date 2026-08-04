<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Guest extends Model
{
    use HasUuids;

    protected $primaryKey = 'uuid';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'tenant_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'passport_number',
        'preferences',
    ];

    protected $casts = [
        'preferences' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'uuid');
    }

    public function aiConversations(): HasMany
    {
        return $this->hasMany(AiConversation::class, 'guest_id', 'uuid');
    }

    public function folios(): HasMany
    {
        return $this->hasMany(Folio::class, 'guest_id', 'uuid');
    }

    public function guestMerges(): HasMany
    {
        return $this->hasMany(GuestMerge::class, 'master_guest_id', 'uuid');
    }

    public function guestMerges(): HasMany
    {
        return $this->hasMany(GuestMerge::class, 'duplicate_guest_id', 'uuid');
    }

    public function guestSentiments(): HasMany
    {
        return $this->hasMany(GuestSentiment::class, 'guest_id', 'uuid');
    }

    public function reservationGuests(): HasMany
    {
        return $this->hasMany(ReservationGuest::class, 'guest_id', 'uuid');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class, 'guest_id', 'uuid');
    }

    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }
}
