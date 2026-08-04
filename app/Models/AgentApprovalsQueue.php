<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AgentApprovalsQueue extends Model
{
    use HasUuids;

    protected $table = 'agent_approvals_queue';

    protected $primaryKey = 'uuid';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'conversation_id',
        'requested_action',
        'arguments',
        'status',
        'reviewer_user_id',
    ];

    protected $casts = [
        'arguments' => 'array',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AiConversation::class, 'conversation_id', 'uuid');
    }

    public function reviewerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_user_id', 'uuid');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
