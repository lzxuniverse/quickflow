<?php

namespace App\Models;

use App\Domains\Property\Models\Property;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class RevenueMetric extends Model
{
    use HasUuids;

    protected $primaryKey = 'property_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'date',
        'property_id',
        'rooms_sold',
        'occupancy_rate',
        'revenue_rooms',
        'revenue_incidentals',
        'adr',
        'revpar',
    ];

    protected $casts = [
        'date' => 'date',
        'rooms_sold' => 'integer',
        'occupancy_rate' => 'decimal:2',
        'revenue_rooms' => 'decimal:2',
        'revenue_incidentals' => 'decimal:2',
        'adr' => 'decimal:2',
        'revpar' => 'decimal:2',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class, 'property_id', 'uuid');
    }

    public function scopeForProperty($query, $propertyId)
    {
        return $query->where('property_id', $propertyId);
    }
}
