<?php

namespace App\Filament\Resources\CmsPages;

use App\Filament\Resources\CmsPages\Pages\CreateCmsPage;
use App\Filament\Resources\CmsPages\Pages\EditCmsPage;
use App\Filament\Resources\CmsPages\Pages\ListCmsPages;
use App\Filament\Resources\CmsPages\Schemas\CmsPageForm;
use App\Filament\Resources\CmsPages\Tables\CmsPagesTable;
use App\Models\CmsPage;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CmsPageResource extends Resource
{
    protected static ?string $model = CmsPage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|\UnitEnum|null $navigationGroup = 'Content';

    public static function form(Schema $schema): Schema
    {
        return CmsPageForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CmsPagesTable::configure($table);
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
            'index' => ListCmsPages::route('/'),
            'create' => CreateCmsPage::route('/create'),
            'edit' => EditCmsPage::route('/{record}/edit'),
        ];
    }
}
