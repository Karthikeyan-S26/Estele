<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Redirect extends Model
{
    protected $fillable = [
        'old_path',
        'new_path',
        'status_code',
        'is_active',
        'source',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'status_code' => 'integer',
    ];

    /**
     * Reduce any URL/path to a comparable form: leading slash, no domain,
     * no query string, no trailing slash (except root), lowercased — so
     * "/Products/Foo/", "https://x.com/products/foo?ref=1" and
     * "products/foo" all normalize to the same "/products/foo".
     */
    public static function normalizePath(string $path): string
    {
        $path = parse_url($path, PHP_URL_PATH) ?: $path;
        $path = '/'.ltrim($path, '/');
        $path = rtrim($path, '/');

        return $path === '' ? '/' : strtolower($path);
    }

    /**
     * Record that $oldPath now permanently redirects to $newPath — the
     * auto-create half of the spec's "slug-history/301 redirect table so
     * old URLs never 404 when slug changes" requirement. Also collapses
     * chains (anything that pointed AT the old path now points straight
     * to the new path) and removes any reverse entry that would otherwise
     * form a redirect loop (A→B while B→A also exists).
     */
    public static function recordSlugChange(string $oldPath, string $newPath): void
    {
        $oldPath = self::normalizePath($oldPath);
        $newPath = self::normalizePath($newPath);

        if ($oldPath === $newPath) {
            return;
        }

        // The new path is live content now — it can never simultaneously be a
        // redirect source (that would 301 a currently-real page away from
        // itself). Covers reverts, e.g. a slug changed A→B→A: once it's back
        // to A, any stale "A→something" row must go, not be updated in place.
        static::where('old_path', $newPath)->delete();

        // Collapse chains: anything that previously pointed at the old path
        // now points straight to the new path instead.
        static::where('new_path', $oldPath)->update(['new_path' => $newPath]);

        static::updateOrCreate(
            ['old_path' => $oldPath],
            [
                'new_path' => $newPath,
                'status_code' => 301,
                'is_active' => true,
                'source' => 'auto',
            ]
        );
    }
}
