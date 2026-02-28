<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    protected $fillable = [
        'subscriber_id',
        'channel_id',
    ];

    public function subscriber(): BelongsTo
    {
        return $this->belongsTo(User::class, 'subscriber_id');
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(User::class, 'channel_id');
    }
}
