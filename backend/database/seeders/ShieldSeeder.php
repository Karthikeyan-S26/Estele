<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Reproduces the two admin roles from source instead of requiring someone to
 * hand-click them in /admin/shield/roles. Previously these only existed in
 * whatever live database the app happened to be pointed at — a `migrate:fresh`
 * silently wiped them with nothing to regenerate them from (bit the Railway
 * deploy once). This seeder is self-sufficient: it creates the permission
 * rows itself rather than assuming `shield:generate` has already been run
 * against this database.
 *
 * If a new Filament resource/widget is added later, either add its name to
 * RESOURCES below, or re-run `php artisan shield:generate --all
 * --panel=admin` and copy the new permission names in here.
 */
class ShieldSeeder extends Seeder
{
    private const RESOURCES = [
        'Banner', 'Category', 'Collection', 'Coupon', 'Offer',
        'HomepageBlock', 'Order', 'Product', 'Role', 'Setting',
        'BlogCategory', 'Blog', 'CmsPage', 'FaqCategory', 'Faq', 'Review',
        'Popup', 'NewsletterSubscriber', 'Redirect', 'Customer',
        'RewardSubmission', 'Vendor', 'OldJewelleryRequest', 'OldJewelleryWalletCredit', 'Staff',
    ];

    private const ACTIONS = [
        'ViewAny', 'View', 'Create', 'Update', 'Delete', 'DeleteAny',
        'Restore', 'RestoreAny', 'ForceDelete', 'ForceDeleteAny',
        'Replicate', 'Reorder',
    ];

    private const WIDGET_PERMISSIONS = [
        'View:StoreStatsWidget',
    ];

    public function run(): void
    {
        $permissionNames = collect(self::RESOURCES)
            ->crossJoin(self::ACTIONS)
            ->map(fn (array $pair) => "{$pair[1]}:{$pair[0]}")
            ->concat(self::WIDGET_PERMISSIONS)
            ->all();

        foreach ($permissionNames as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $superAdmin->syncPermissions($permissionNames);

        // Product/Category/content management only — explicitly no Order/Coupon
        // (financial/operational data) or Settings access.
        $marketing = Role::firstOrCreate(['name' => 'marketing', 'guard_name' => 'web']);
        $marketing->syncPermissions([
            'ViewAny:Banner', 'View:Banner',
            'ViewAny:Category', 'View:Category', 'Create:Category', 'Update:Category',
            'ViewAny:Product', 'View:Product', 'Create:Product', 'Update:Product',
            'ViewAny:BlogCategory', 'View:BlogCategory', 'Create:BlogCategory', 'Update:BlogCategory',
            'ViewAny:Blog', 'View:Blog', 'Create:Blog', 'Update:Blog',
            'ViewAny:CmsPage', 'View:CmsPage', 'Create:CmsPage', 'Update:CmsPage',
            'ViewAny:FaqCategory', 'View:FaqCategory', 'Create:FaqCategory', 'Update:FaqCategory',
            'ViewAny:Faq', 'View:Faq', 'Create:Faq', 'Update:Faq',
            // Moderation only — no Delete/DeleteAny, matches the resource's own
            // canCreate()=false (reviews only ever originate from customers).
            'ViewAny:Review', 'View:Review', 'Update:Review',
            // Popups are a marketing tool end-to-end — full CRUD, unlike Review.
            'ViewAny:Popup', 'View:Popup', 'Create:Popup', 'Update:Popup', 'Delete:Popup',
            // Subscriber list is read-only everywhere — nobody hand-edits captured emails.
            'ViewAny:NewsletterSubscriber', 'View:NewsletterSubscriber',
            // Reviews and approves/denies customer reward-submission videos —
            // moderation of customer-submitted content, the same job as
            // Review above. Update (not Create/Delete): the approve/reject
            // actions on RewardSubmissionsTable both operate via an update,
            // and the resource's own canCreate() is already false.
            //
            // This used to live on the 'vendor' role, back when that name
            // meant "a staff reviewer" — before App\Models\Vendor (the
            // old-jewellery bidding marketplace contact) was built and
            // reused the same role name. That left every jewellery-bidding
            // vendor contact able to approve unrelated customer reward
            // claims, and RewardSubmissionController::store() emailing every
            // one of them about it. See the migration alongside this file
            // for how existing databases are corrected.
            'ViewAny:RewardSubmission', 'View:RewardSubmission', 'Update:RewardSubmission',
        ]);

        // Read-only access to the old-jewellery requests it was invited to
        // bid on (migration 2026_09_12_120100_give_vendor_role_...) — kept in
        // sync here because syncPermissions() below replaces the role's
        // entire permission set, so re-running this seeder would otherwise
        // silently revoke what that migration granted. This is the ONLY
        // thing a vendor contact's login can see — see VendorForm's "Panel
        // access" note, which is the one place an admin should need to check
        // what a vendor gets.
        $vendor = Role::firstOrCreate(['name' => 'vendor', 'guard_name' => 'web']);
        $vendor->syncPermissions([
            'ViewAny:OldJewelleryRequest', 'View:OldJewelleryRequest',
        ]);
    }
}
