<?php

namespace App\Models;

use App\Domains\Property\Models\Property;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Tenant extends Model
{
    use HasUuids;

    protected $primaryKey = 'uuid';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'plan_tier',
        'status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function aiAssistants(): HasMany
    {
        return $this->hasMany(AiAssistant::class, 'tenant_id', 'uuid');
    }

    public function apiKeies(): HasMany
    {
        return $this->hasMany(ApiKey::class, 'tenant_id', 'uuid');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'tenant_id', 'uuid');
    }

    public function automationRules(): HasMany
    {
        return $this->hasMany(AutomationRule::class, 'tenant_id', 'uuid');
    }

    public function customFieldDefinitions(): HasMany
    {
        return $this->hasMany(CustomFieldDefinition::class, 'tenant_id', 'uuid');
    }

    public function guests(): HasMany
    {
        return $this->hasMany(Guest::class, 'tenant_id', 'uuid');
    }

    public function knowledgeBases(): HasMany
    {
        return $this->hasMany(KnowledgeBase::class, 'tenant_id', 'uuid');
    }

    public function mcpServers(): HasMany
    {
        return $this->hasMany(McpServer::class, 'tenant_id', 'uuid');
    }

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class, 'tenant_id', 'uuid');
    }

    public function tenantFeatures(): HasMany
    {
        return $this->hasMany(TenantFeature::class, 'tenant_id', 'uuid');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'tenant_id', 'uuid');
    }

    public function webhookEndpoints(): HasMany
    {
        return $this->hasMany(WebhookEndpoint::class, 'tenant_id', 'uuid');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
