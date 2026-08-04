<?php

namespace App\Domains\Property\Models;

use App\Models\Tenant;
use App\Models\Allotment;
use App\Models\BookingPaceSnapshot;
use App\Models\ChannelCredential;
use App\Models\IcalConnection;
use App\Models\PmsConnection;
use App\Models\RatePlan;
use App\Models\Reservation;
use App\Models\RevenueMetric;
use App\Models\Review;
use App\Models\RoomType;
use App\Models\Service;
use App\Models\TaxRate;
use App\Models\PropertySetting;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Property extends Model
{
    use HasUuids;

    protected $primaryKey = 'uuid';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'tenant_id',
        'name',
        'timezone',
        'currency',
        'address_street',
        'address_city',
        'address_state',
        'address_postal_code',
        'address_country',
        'lat',
        'lng',
        'contact_phone',
        'contact_email',
        'status',
    ];

    protected $casts = [
        'lat' => 'decimal:2',
        'lng' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'uuid');
    }

    public function allotments(): HasMany
    {
        return $this->hasMany(Allotment::class, 'property_id', 'uuid');
    }

    public function bookingPaceSnapshots(): HasMany
    {
        return $this->hasMany(BookingPaceSnapshot::class, 'property_id', 'uuid');
    }

    public function channelCredentials(): HasMany
    {
        return $this->hasMany(ChannelCredential::class, 'property_id', 'uuid');
    }

    public function icalConnections(): HasMany
    {
        return $this->hasMany(IcalConnection::class, 'property_id', 'uuid');
    }

    public function pmsConnections(): HasMany
    {
        return $this->hasMany(PmsConnection::class, 'property_id', 'uuid');
    }

    public function ratePlans(): HasMany
    {
        return $this->hasMany(RatePlan::class, 'property_id', 'uuid');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class, 'property_id', 'uuid');
    }

    public function revenueMetrics(): HasMany
    {
        return $this->hasMany(RevenueMetric::class, 'property_id', 'uuid');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'property_id', 'uuid');
    }

    public function roomTypes(): HasMany
    {
        return $this->hasMany(RoomType::class, 'property_id', 'uuid');
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class, 'property_id', 'uuid');
    }

    public function taxRates(): HasMany
    {
        return $this->hasMany(TaxRate::class, 'property_id', 'uuid');
    }

    public function propertySetting(): HasOne
    {
        return $this->hasOne(PropertySetting::class, 'property_id', 'uuid');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }
}
