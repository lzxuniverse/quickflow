<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DerivedRateFormula extends Model
{
    use HasUuids;

    protected $primaryKey = 'uuid';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'target_rate_plan_id',
        'modifier_type',
        'modifier_value',
    ];

    protected $casts = [
        'modifier_value' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function targetRatePlan(): BelongsTo
    {
        return $this->belongsTo(RatePlan::class, 'target_rate_plan_id', 'uuid');
    }
}
