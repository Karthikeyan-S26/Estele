<?php

namespace App\Models;

use App\Models\Concerns\HasSeoMeta;
use Illuminate\Database\Eloquent\Model;

class CmsPage extends Model
{
    use HasSeoMeta;

    protected $fillable = [
        'title',
        'slug',
        'content',
        'status',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
