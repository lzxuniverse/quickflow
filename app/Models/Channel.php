<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Channel extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'integration_mode',
    ];

    protected $casts = [
        'integration_mode' => 'array',
    ];

    public function channelCredentials(): HasMany
    {
        return $this->hasMany(ChannelCredential::class, 'channel_id', 'id');
    }
}
