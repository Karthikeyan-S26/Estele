<?php

namespace App\Filament\Concerns;

use App\Filament\Support\NavigationSeen;
use Illuminate\Database\Eloquent\Builder;

trait HasNewRecordsBadge
{
    public static function getNavigationBadge(): ?string
    {
        return NavigationSeen::badge(str_replace('/', '.', static::getSlug()), static::getNewRecordsQueries(), static::countsBacklogInBadge());
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'New since your last visit';
    }

    /**
     * @return array<Builder>
     */
    protected static function getNewRecordsQueries(): array
    {
        return [static::getEloquentQuery()];
    }

    protected static function countsBacklogInBadge(): bool
    {
        return false;
    }
}
