<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * The 'vendor' Spatie role was named before App\Models\Vendor (the
 * old-jewellery bidding marketplace contact) existed — it originally meant
 * "staff who reviews customer reward-submission videos." Both meanings ended
 * up sharing the one role name, so every jewellery-bidding vendor a super
 * admin invited could also approve/reject unrelated customer reward claims,
 * and got emailed about every new one (RewardSubmissionController::store()
 * notifies User::role(['vendor', ...])).
 *
 * This moves that capability to the 'marketing' role instead, which already
 * moderates customer-submitted content (Review). See ShieldSeeder for the
 * source-of-truth version of both role's permission sets.
 */
return new class extends Migration
{
    private const PERMISSIONS = [
        'ViewAny:RewardSubmission', 'View:RewardSubmission', 'Update:RewardSubmission',
    ];

    public function up(): void
    {
        $permissions = collect(self::PERMISSIONS)
            ->map(fn (string $name) => Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']));

        $vendor = Role::where(['name' => 'vendor', 'guard_name' => 'web'])->first();
        $vendor?->revokePermissionTo($permissions);

        $marketing = Role::firstOrCreate(['name' => 'marketing', 'guard_name' => 'web']);
        foreach ($permissions as $permission) {
            if (! $marketing->hasPermissionTo($permission)) {
                $marketing->givePermissionTo($permission);
            }
        }
    }

    public function down(): void
    {
        $permissions = Permission::whereIn('name', self::PERMISSIONS)->where('guard_name', 'web')->get();

        $marketing = Role::where(['name' => 'marketing', 'guard_name' => 'web'])->first();
        $marketing?->revokePermissionTo($permissions);

        $vendor = Role::firstOrCreate(['name' => 'vendor', 'guard_name' => 'web']);
        foreach ($permissions as $permission) {
            if (! $vendor->hasPermissionTo($permission)) {
                $vendor->givePermissionTo($permission);
            }
        }
    }
};
