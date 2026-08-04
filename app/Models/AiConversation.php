<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AiConversation extends Model
{
    use HasUuids;

    protected $primaryKey = 'uuid';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'assistant_id',
        'guest_id',
        'reservation_id',
        'status',
    ];

    public function assistant(): BelongsTo
    {
        return $this->belongsTo(AiAssistant::class, 'assistant_id', 'uuid');
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class, 'guest_id', 'uuid');
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class, 'reservation_id', 'uuid');
    }

    public function agentApprovalsQueues(): HasMany
    {
        return $this->hasMany(AgentApprovalsQueue::class, 'conversation_id', 'uuid');
    }

    public function aiMessages(): HasMany
    {
        return $this->hasMany(AiMessage::class, 'conversation_id', 'uuid');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
