<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Redirect Manager (spec §5/§6 — "Old URL → New URL mapping (301),
     * auto-created when a slug changes"). Paths are stored relative
     * (leading slash, no domain/query string) so the same row works
     * whether the app is served from localhost or the live domain.
     */
    public function up(): void
    {
        Schema::create('redirects', function (Blueprint $table) {
            $table->id();
            $table->string('old_path')->unique();
            $table->string('new_path');
            $table->unsignedSmallInteger('status_code')->default(301);
            $table->boolean('is_active')->default(true);
            // 'auto' rows come from a slug change (see Redirect::recordSlugChange,
            // wired into the Product/Category/Collection/Blog/CmsPage observers);
            // 'manual' rows are admin-authored via the Redirects Filament resource.
            $table->string('source')->default('manual');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('redirects');
    }
};
