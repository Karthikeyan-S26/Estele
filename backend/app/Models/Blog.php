<?php

namespace App\Models;

use App\Models\Concerns\HasSeoMeta;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Blog extends Model implements HasMedia
{
    use HasSeoMeta;
    use InteractsWithMedia;

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('featured_image')
            ->useDisk('original_images')
            ->storeConversionsOnDisk('public')
            ->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        // Sizes/quality follow the responsive image spec (§3.1).
        $this->addMediaConversion('card')->width(400)->format('png')->quality(78);
        $this->addMediaConversion('mobile')->width(768)->format('png')->quality(80);
        $this->addMediaConversion('tablet')->width(1024)->format('png')->quality(82);
        $this->addMediaConversion('detail')->width(1600)->format('png')->quality(83);
    }

    protected $fillable = [
        'blog_category_id',
        'title',
        'slug',
        'excerpt',
        'content',
        'author_name',
        'status',
        'published_at',
        'is_featured',
        'featured_image_alt_text',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'is_featured' => 'boolean',
        ];
    }

    public function blogCategory(): BelongsTo
    {
        return $this->belongsTo(BlogCategory::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')
            ->where('published_at', '<=', now());
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
