<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NewsletterSubscriber extends Model
{
    protected $fillable = [
        'email',
        'source',
        'popup_id',
    ];

    public function popup(): BelongsTo
    {
        return $this->belongsTo(Popup::class);
    }
}
