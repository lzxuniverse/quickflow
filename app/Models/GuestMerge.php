<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class GuestMerge extends Model
{
    protected $fillable = [
        'master_guest_id',
        'duplicate_guest_id',
        'merged_at',
    ];

    protected $casts = [
        'id' => 'integer',
        'merged_at' => 'datetime',
    ];

    public function masterGuest(): BelongsTo
    {
        return $this->belongsTo(Guest::class, 'master_guest_id', 'uuid');
    }

    public function duplicateGuest(): BelongsTo
    {
        return $this->belongsTo(Guest::class, 'duplicate_guest_id', 'uuid');
    }
}
