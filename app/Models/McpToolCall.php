<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class McpToolCall extends Model
{
    use HasUuids;

    protected $primaryKey = 'uuid';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'mcp_server_id',
        'tool_name',
        'arguments',
        'response',
        'status',
    ];

    protected $casts = [
        'arguments' => 'array',
        'created_at' => 'datetime',
    ];

    public function mcpServer(): BelongsTo
    {
        return $this->belongsTo(McpServer::class, 'mcp_server_id', 'uuid');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
