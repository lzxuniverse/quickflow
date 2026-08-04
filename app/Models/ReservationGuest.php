<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ReservationGuest extends Model
{
    protected $fillable = [
        'reservation_id',
        'guest_id',
        'is_primary',
    ];

    protected $casts = [
        'id' => 'integer',
        'is_primary' => 'boolean',
    ];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class, 'reservation_id', 'uuid');
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class, 'guest_id', 'uuid');
    }
}
