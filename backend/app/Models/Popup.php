<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Popup extends Model implements HasMedia
{
    use InteractsWithMedia;

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')
            ->useDisk('original_images')
            ->storeConversionsOnDisk('public')
            ->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('card')->width(600)->format('png')->quality(82);
    }

    protected $fillable = [
        'name',
        'type',
        'trigger',
        'delay_seconds',
        'title',
        'body',
        'cta_label',
        'cta_url',
        'discount_code',
        'show_email_field',
        'is_active',
        'starts_at',
        'ends_at',
        'target_new_visitors_only',
        'sort_order',
        'image_alt_text',
    ];

    protected function casts(): array
    {
        return [
            'delay_seconds' => 'integer',
            'show_email_field' => 'boolean',
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'target_new_visitors_only' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function subscribers(): HasMany
    {
        return $this->hasMany(NewsletterSubscriber::class);
    }

    public function scopeEligible(Builder $query): Builder
    {
        $now = Carbon::now();

        return $query->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now));
    }
}
