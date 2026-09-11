<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Data cleanup for the "Your Budget, Your Bling" (price_tiers) and "Mail
     * Subscription" (newsletter) homepage sections being removed from the
     * site. home/index.blade.php already unconditionally skips rendering
     * these two types (see the ->reject() call there), so this migration
     * isn't required for the removal to take effect on the public site —
     * it exists purely so the admin's Homepage Blocks list stops showing
     * these rows as "Active" when they're actually unrenderable, which
     * would otherwise be confusing. Deploy already runs `migrate --force`
     * on every container start (see docker/start.sh), so this runs itself
     * against production without needing direct DB/SSH access — same logic
     * as the standalone `homepage-blocks:deactivate-removed` artisan
     * command (kept as-is for any environment where migrations aren't the
     * deploy trigger), just expressed as a one-off migration instead so it
     * actually executes against Railway.
     */
    public function up(): void
    {
        DB::table('homepage_blocks')
            ->whereIn('type', ['price_tiers', 'newsletter'])
            ->where('is_active', true)
            ->update(['is_active' => false]);

        // Mirrors the standalone command's cache flush. Guarded: migrations
        // run before the app is confirmed fully healthy, and a cache-tag
        // failure here must never fail the deploy over a non-essential
        // invalidation (nothing user-visible reads is_active for these two
        // types anyway, since home/index.blade.php skips them by type).
        try {
            Cache::tags(['home'])->flush();
        } catch (\Throwable $e) {
            // no-op — see comment above
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Deliberately a no-op: reactivating these would just make them
        // "Active but unrenderable" again in the admin list, which is the
        // exact confusing state this migration exists to fix. If the
        // sections are ever brought back, that's a real feature-flag
        // decision (re-add the type to HomepageBlockForm's Select and
        // remove it from home/index.blade.php's ->reject() list), not
        // something a migration rollback should silently do.
    }
};
