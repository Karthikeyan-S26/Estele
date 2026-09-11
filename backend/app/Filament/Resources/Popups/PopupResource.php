<?php

namespace App\Filament\Resources\Popups;

use App\Filament\Resources\Popups\Pages\CreatePopup;
use App\Filament\Resources\Popups\Pages\EditPopup;
use App\Filament\Resources\Popups\Pages\ListPopups;
use App\Filament\Resources\Popups\Schemas\PopupForm;
use App\Filament\Resources\Popups\Tables\PopupsTable;
use App\Models\Popup;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PopupResource extends Resource
{
    protected static ?string $model = Popup::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static string|\UnitEnum|null $navigationGroup = 'Content';

    public static function form(Schema $schema): Schema
    {
        return PopupForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PopupsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPopups::route('/'),
            'create' => CreatePopup::route('/create'),
            'edit' => EditPopup::route('/{record}/edit'),
        ];
    }
}
