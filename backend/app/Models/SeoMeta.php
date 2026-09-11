<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SeoMeta extends Model
{
    protected $table = 'seo_meta';

    protected $fillable = [
        'title',
        'description',
        'og_image',
        'canonical',
    ];

    public function metable(): MorphTo
    {
        return $this->morphTo();
    }
}
