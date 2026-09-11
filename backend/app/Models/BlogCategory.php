<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BlogCategory extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'sort_order',
    ];

    public function blogs(): HasMany
    {
        return $this->hasMany(Blog::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
