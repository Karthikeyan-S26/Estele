<?php

namespace App\Console\Commands;

use App\Models\HomepageBlock;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * One-off cleanup for the "Your Budget, Your Bling" (price_tiers) and "Mail
 * Subscription" (newsletter) homepage sections being removed from the site.
 * home/index.blade.php already skips rendering these types regardless, so
 * this command isn't required for the removal to take effect — it just also
 * flips is_active=false so they read correctly as "off" in the admin list
 * rather than "active" but silently unrendered. Safe to run more than once.
 *
 * Run once per environment after deploying:
 *   php artisan homepage-blocks:deactivate-removed
 */
class DeactivateRemovedHomepageBlocks extends Command
{
    protected $signature = 'homepage-blocks:deactivate-removed';

    protected $description = 'Deactivate any existing price_tiers/newsletter homepage blocks (sections removed from the site)';

    public function handle(): int
    {
        $count = HomepageBlock::whereIn('type', ['price_tiers', 'newsletter'])
            ->where('is_active', true)
            ->update(['is_active' => false]);

        Cache::tags(['home'])->flush();

        $this->info("Deactivated {$count} homepage block(s).");

        return self::SUCCESS;
    }
}
