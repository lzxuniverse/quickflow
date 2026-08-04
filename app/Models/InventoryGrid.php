<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class InventoryGrid extends Model
{
    use HasUuids;

    protected $table = 'inventory_grid';

    protected $primaryKey = 'room_type_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'date',
        'room_type_id',
        'total_physical_units',
        'blocked_maintenance',
        'reserved_units',
        'available_to_sell',
    ];

    protected $casts = [
        'date' => 'date',
        'total_physical_units' => 'integer',
        'blocked_maintenance' => 'integer',
        'reserved_units' => 'integer',
        'available_to_sell' => 'integer',
    ];

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class, 'room_type_id', 'uuid');
    }
}
