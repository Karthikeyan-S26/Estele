<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SellAuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'sell_request_id',
        'actor_type',
        'actor_id',
        'event',
        'from_status',
        'to_status',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function sellRequest(): BelongsTo
    {
        return $this->belongsTo(SellRequest::class);
    }

    public function actor()
    {
        return $this->morphTo();
    }
}