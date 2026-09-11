<?php

declare(strict_types=1);

namespace App\Filament\Resources\Roles;

use App\Filament\Resources\Roles\Pages\CreateRole;
use App\Filament\Resources\Roles\Pages\EditRole;
use App\Filament\Resources\Roles\Pages\ListRoles;
use App\Filament\Resources\Roles\Pages\ViewRole;
use BezhanSalleh\FilamentShield\Resources\Roles\RoleResource as ShieldRoleResource;
use Filament\Tables\Enums\PaginationMode;
use Filament\Tables\Table;
use Override;

/**
 * Publishes filament-shield's own RoleResource (see
 * Utils::isResourcePublished()) purely to route around this local
 * environment's missing ext-intl extension, which
 * Illuminate\Support\Number::format() requires — the same fix already
 * applied to every other table in this codebase (commits 264d23b,
 * 8d6fc72). Two call sites hit it here: the pagination footer (fixed by
 * ->paginationMode(Simple)) and the bulk-selection banner, which renders
 * as soon as any ->toolbarActions() are registered — removing
 * DeleteBulkAction alone doesn't disable selection in Filament v5,
 * ->disabledSelection() does. Everything else is inherited from the
 * vendor resource unchanged.
 */
class RoleResource extends ShieldRoleResource
{
    #[Override]
    public static function table(Table $table): Table
    {
        return parent::table($table)
            ->paginationMode(PaginationMode::Simple)
            ->disabledSelection()
            ->toolbarActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRoles::route('/'),
            'create' => CreateRole::route('/create'),
            'view' => ViewRole::route('/{record}'),
            'edit' => EditRole::route('/{record}/edit'),
        ];
    }
}
