<?php

namespace App\Models\Concerns;

use App\Models\SeoMeta;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait HasSeoMeta
{
    /**
     * seo_meta is polymorphic, so morphs() gives it an index but never a
     * foreign key — nothing at the database level clears the row when the
     * owner is deleted. Left behind, a later record that reuses the same
     * auto-increment id silently inherits the deleted one's meta title,
     * description and canonical URL.
     */
    public static function bootHasSeoMeta(): void
    {
        static::deleting(function ($model) {
            // Only on a real delete: if any of these models later adopts
            // SoftDeletes, a recoverable delete must keep its meta.
            if (method_exists($model, 'isForceDeleting') && ! $model->isForceDeleting()) {
                return;
            }

            $model->seoMeta()->delete();
        });
    }

    public function seoMeta(): MorphOne
    {
        return $this->morphOne(SeoMeta::class, 'metable');
    }
}
