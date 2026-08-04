<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PriceGrid extends Model
{
    use HasUuids;

    protected $table = 'price_grid';

    protected $primaryKey = 'rate_plan_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'date',
        'rate_plan_id',
        'price',
        'extra_adult_charge',
        'extra_child_charge',
    ];

    protected $casts = [
        'date' => 'date',
        'price' => 'decimal:2',
        'extra_adult_charge' => 'decimal:2',
        'extra_child_charge' => 'decimal:2',
    ];

    public function ratePlan(): BelongsTo
    {
        return $this->belongsTo(RatePlan::class, 'rate_plan_id', 'uuid');
    }
}
