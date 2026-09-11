<?php

namespace App\Filament\Clusters\CouponsAndOffers;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

class CouponsAndOffersCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static ?string $navigationLabel = 'Coupons & Offers';

    // See OrderResource for the sidebar-order rationale.
    protected static ?int $navigationSort = 60;
}
