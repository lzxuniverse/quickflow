<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class GuestSentiment extends Model
{
    protected $fillable = [
        'guest_id',
        'sentiment_score',
        'last_detected_at',
    ];

    protected $casts = [
        'id' => 'integer',
        'sentiment_score' => 'decimal:2',
        'last_detected_at' => 'datetime',
    ];

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class, 'guest_id', 'uuid');
    }
}
