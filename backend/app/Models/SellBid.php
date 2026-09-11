<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SellBid extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_WITHDRAWN = 'withdrawn';

    protected $fillable = [
        'sell_request_id',
        'vendor_id',
        'amount',
        'status',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'submitted_at' => 'datetime',
        ];
    }

    public function sellRequest(): BelongsTo
    {
        return $this->belongsTo(SellRequest::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }
}