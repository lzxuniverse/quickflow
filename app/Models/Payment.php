<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Payment extends Model
{
    use HasUuids;

    protected $primaryKey = 'uuid';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'folio_id',
        'payment_method',
        'amount',
        'gateway_transaction_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    public function folio(): BelongsTo
    {
        return $this->belongsTo(Folio::class, 'folio_id', 'uuid');
    }

    public function transactionAllocations(): HasMany
    {
        return $this->hasMany(TransactionAllocation::class, 'payment_id', 'uuid');
    }
}
