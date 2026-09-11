<?php

namespace App\Filament\Resources\SellRequests;

use App\Filament\Resources\SellRequests\Pages\EditSellRequest;
use App\Filament\Resources\SellRequests\Pages\ListSellRequests;
use App\Filament\Resources\SellRequests\RelationManagers\SellBidsManager;
use App\Filament\Resources\SellRequests\RelationManagers\SellInvitationsManager;
use App\Filament\Resources\SellRequests\RelationManagers\SellAuditLogsManager;
use App\Filament\Resources\SellRequests\Schemas\SellRequestForm;
use App\Filament\Resources\SellRequests\Tables\SellRequestsTable;
use App\Models\SellRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SellRequestsResource extends Resource
{
    protected static ?string $model = SellRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|\UnitEnum|null $navigationGroup = 'Commerce';

    protected static ?string $navigationLabel = 'Old Jewellery Buy-Back';

    protected static ?string $slug = 'sell-requests';

    public static function form(Schema $schema): Schema
    {
        return SellRequestForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SellRequestsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            SellInvitationsManager::class,
            SellBidsManager::class,
            SellAuditLogsManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSellRequests::route('/'),
            'edit' => EditSellRequest::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}