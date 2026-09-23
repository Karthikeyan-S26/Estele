<?php

use App\Services\OldJewellery\OldJewelleryRequestService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Moves the old-jewellery counter from per-year (`year`) to a single global
 * key (`sequence_key`), matching OldJewelleryRequestService::SEQUENCE_KEY.
 *
 * DEPLOY-SAFETY (mobile API shares the production database): existing
 * `request_number` values are website-visible (route key) and are NEVER
 * rewritten here. The API looks requests up by column equality
 * (OldJewelleryRequest::getRouteKeyName), so old OJ-{year}-{nnnnnn} numbers
 * keep working unchanged alongside newly issued 001, 002, ... numbers.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Structural part only, guarded so re-runs and already-migrated
        // databases are no-ops. No code in this codebase reads the `year`
        // column (verified), and MySQL renames preserve all values.
        if (Schema::hasColumn('old_jewellery_number_sequences', 'year')
            && ! Schema::hasColumn('old_jewellery_number_sequences', 'sequence_key')) {
            // 'year' now holds a sequence key, not a year — varchar(4) is too
            // narrow for it. Renamed in the same breath so the column name stops
            // lying about its contents.
            Schema::table('old_jewellery_number_sequences', function (Blueprint $table) {
                $table->string('year', 32)->change();
            });

            Schema::table('old_jewellery_number_sequences', function (Blueprint $table) {
                $table->renameColumn('year', 'sequence_key');
            });
        }

        DB::transaction(function () {
            $requestCount = DB::table('old_jewellery_requests')->count();

            $existing = DB::table('old_jewellery_number_sequences')
                ->where('sequence_key', OldJewelleryRequestService::SEQUENCE_KEY)
                ->first();

            // Never move the counter backwards: newly issued numbers must
            // never collide with existing ones on the UNIQUE request_number.
            $lastValue = max((int) ($existing->last_value ?? 0), $requestCount);

            if ($existing) {
                if ($lastValue > (int) $existing->last_value) {
                    DB::table('old_jewellery_number_sequences')
                        ->where('sequence_key', OldJewelleryRequestService::SEQUENCE_KEY)
                        ->update(['last_value' => $lastValue]);
                }
            } else {
                DB::table('old_jewellery_number_sequences')->insert([
                    'sequence_key' => OldJewelleryRequestService::SEQUENCE_KEY,
                    'last_value' => $lastValue,
                ]);
            }
        });
    }

    public function down(): void
    {
        DB::transaction(function () {
            $requests = DB::table('old_jewellery_requests')
                ->orderBy('created_at')
                ->orderBy('id')
                ->get(['id', 'created_at']);

            foreach ($requests as $index => $request) {
                DB::table('old_jewellery_requests')
                    ->where('id', $request->id)
                    ->update(['request_number' => 'tmp-'.($index + 1)]);
            }

            $perYear = [];

            foreach ($requests as $request) {
                $year = date('Y', strtotime($request->created_at));
                $perYear[$year] = ($perYear[$year] ?? 0) + 1;

                DB::table('old_jewellery_requests')
                    ->where('id', $request->id)
                    ->update(['request_number' => sprintf('OJ-%s-%06d', $year, $perYear[$year])]);
            }

            DB::table('old_jewellery_number_sequences')->delete();

            foreach ($perYear as $year => $last) {
                DB::table('old_jewellery_number_sequences')->insert([
                    'sequence_key' => $year,
                    'last_value' => $last,
                ]);
            }
        });

        Schema::table('old_jewellery_number_sequences', function (Blueprint $table) {
            $table->renameColumn('sequence_key', 'year');
        });

        Schema::table('old_jewellery_number_sequences', function (Blueprint $table) {
            $table->string('year', 4)->change();
        });
    }
};
