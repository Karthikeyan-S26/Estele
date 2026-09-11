<?php

namespace App\Filament\Clusters\Seo;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

class SeoCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMagnifyingGlass;

    protected static ?string $navigationLabel = 'SEO';

    // See OrderResource for the sidebar-order rationale.
    protected static ?int $navigationSort = 90;
}
