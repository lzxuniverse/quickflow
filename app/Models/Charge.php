<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Charge extends Model
{
    use HasUuids;

    protected $primaryKey = 'uuid';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'folio_id',
        'service_id',
        'description',
        'base_amount',
        'tax_amount',
        'is_void',
    ];

    protected $casts = [
        'base_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'is_void' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function folio(): BelongsTo
    {
        return $this->belongsTo(Folio::class, 'folio_id', 'uuid');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id', 'uuid');
    }

    public function transactionAllocations(): HasMany
    {
        return $this->hasMany(TransactionAllocation::class, 'charge_id', 'uuid');
    }
}
