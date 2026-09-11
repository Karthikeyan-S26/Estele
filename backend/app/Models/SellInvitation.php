<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SellInvitation extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_DECLINED = 'declined';
    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'sell_request_id',
        'vendor_id',
        'token',
        'status',
        'sent_at',
        'responded_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'responded_at' => 'datetime',
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

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isAccepted(): bool
    {
        return $this->status === self::STATUS_ACCEPTED;
    }
}