<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Banner extends Model implements HasMedia
{
    use InteractsWithMedia;

    public function registerMediaCollections(): void
    {
        // 'image' = Desktop Banner (required). 'mobile_image' = Mobile Banner
        // (optional) — added so admins can upload a differently-cropped/composed
        // image for mobile instead of just a resized copy of the desktop art.
        // Kept as two collections, not one with two conversions, since the
        // whole point is letting them be different source images.
        $this->addMediaCollection('image')
            ->useDisk('original_images')
            ->storeConversionsOnDisk('public')
            ->singleFile();

        $this->addMediaCollection('mobile_image')
            ->useDisk('original_images')
            ->storeConversionsOnDisk('public')
            ->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('desktop')->width(1600)->format('png')->quality(83);
        // Still generated on 'image' too: existing banners with no mobile_image
        // upload fall back to this as their mobile source (see getMobileImageUrl()).
        $this->addMediaConversion('mobile')->width(768)->format('png')->quality(80);
    }

    /**
     * Mobile-optimized image URL: the dedicated mobile upload if the admin
     * provided one, else the desktop image's 'mobile' conversion (unchanged
     * behavior for every banner that predates the mobile_image field).
     */
    public function getMobileImageUrl(): ?string
    {
        if ($this->hasMedia('mobile_image')) {
            return $this->getFirstMediaUrl('mobile_image');
        }

        return $this->hasMedia('image') ? $this->getFirstMediaUrl('image', 'mobile') : null;
    }

    protected $fillable = [
        'title',
        'link_url',
        'sort_order',
        'is_active',
        'image_alt_text',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }
}
