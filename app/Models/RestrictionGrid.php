<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class RestrictionGrid extends Model
{
    use HasUuids;

    protected $table = 'restriction_grid';

    protected $primaryKey = 'rate_plan_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'date',
        'rate_plan_id',
        'min_los',
        'max_los',
        'closed_to_arrival',
        'closed_to_departure',
        'stop_sell',
    ];

    protected $casts = [
        'date' => 'date',
        'min_los' => 'integer',
        'max_los' => 'integer',
        'closed_to_arrival' => 'boolean',
        'closed_to_departure' => 'boolean',
        'stop_sell' => 'boolean',
    ];

    public function ratePlan(): BelongsTo
    {
        return $this->belongsTo(RatePlan::class, 'rate_plan_id', 'uuid');
    }
}
