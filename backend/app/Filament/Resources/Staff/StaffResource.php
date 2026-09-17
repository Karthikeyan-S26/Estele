<?php

namespace App\Filament\Resources\Staff;

use App\Filament\Concerns\HasNewRecordsBadge;
use App\Filament\Resources\Staff\Pages\ListStaff;
use App\Filament\Resources\Staff\Tables\StaffTable;
use App\Models\User;
use App\Models\Vendor;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Who can sign in to the panel. Roles (the sibling screen) decide what each
 * role may do; this screen only decides which people hold which role.
 *
 * A person who is ONLY a vendor contact (their sole role is 'vendor' or
 * 'admin') is excluded — that login is fully owned by the Vendors screen.
 * But someone who ALSO holds a real staff role stays listed here even if
 * they're additionally a vendor contact, with that fact shown on the row
 * (see StaffTable) — hiding them the moment a Vendor row is attached is
 * exactly the "person disappears when I touch the other screen" bug this
 * once had: adding the same email as a vendor contact silently removed
 * them from Staff, and nothing on either screen explained why.
 *
 * Authorisation is keyed on its own "Staff" permission set rather than the
 * User model's policy, which the Customers screen already owns.
 */
class StaffResource extends Resource
{
    use HasNewRecordsBadge;

    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static ?string $navigationLabel = 'Staff';

    protected static ?string $modelLabel = 'Staff member';

    protected static ?string $pluralModelLabel = 'Staff';

    protected static string|\UnitEnum|null $navigationGroup = 'Team';

    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->has('roles')
            ->where(function (Builder $query) {
                $query->whereDoesntHave('vendor')
                    ->orWhereHas('roles', fn (Builder $roles) => $roles->whereNotIn('name', Vendor::ACCESS_ROLES));
            })
            ->with(['roles', 'vendor']);
    }

    public static function table(Table $table): Table
    {
        return StaffTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStaff::route('/'),
        ];
    }

    public static function canViewAny(): bool
    {
        return (bool) auth()->user()?->can('ViewAny:Staff');
    }

    public static function canCreate(): bool
    {
        return (bool) auth()->user()?->can('Create:Staff');
    }

    public static function canEdit(Model $record): bool
    {
        return (bool) auth()->user()?->can('Update:Staff');
    }

    public static function canDelete(Model $record): bool
    {
        return (bool) auth()->user()?->can('Delete:Staff');
    }

    public static function canView(Model $record): bool
    {
        return (bool) auth()->user()?->can('View:Staff');
    }
}
