<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class HomepageBlockItem extends Model implements HasMedia
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
        $this->addMediaConversion('card')->width(768)->format('png')->quality(80);
        $this->addMediaConversion('banner')->width(1600)->format('png')->quality(83);
        // USP rows render this at 36-40px. Originals live on the private
        // 'original_images' disk and are never servable, so the icon needs a
        // conversion of its own rather than borrowing the 768px 'card'.
        $this->addMediaConversion('icon')->width(120)->format('png')->quality(85);
    }

    protected $fillable = [
        'homepage_block_id',
        'title',
        'body',
        'link_url',
        'rating',
        'itemable_type',
        'itemable_id',
        'sort_order',
    ];

    public function homepageBlock(): BelongsTo
    {
        return $this->belongsTo(HomepageBlock::class);
    }

    public function itemable(): MorphTo
    {
        return $this->morphTo();
    }
}
