<?php

namespace App\Filament\Resources\HomepageBlocks;

use App\Filament\Resources\HomepageBlocks\Pages\CreateHomepageBlock;
use App\Filament\Resources\HomepageBlocks\Pages\EditHomepageBlock;
use App\Filament\Resources\HomepageBlocks\Pages\ListHomepageBlocks;
use App\Filament\Resources\HomepageBlocks\Schemas\HomepageBlockForm;
use App\Filament\Resources\HomepageBlocks\Tables\HomepageBlocksTable;
use App\Models\HomepageBlock;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class HomepageBlockResource extends Resource
{
    protected static ?string $model = HomepageBlock::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    // See OrderResource for the sidebar-order rationale.
    protected static ?int $navigationSort = 70;

    public static function form(Schema $schema): Schema
    {
        return HomepageBlockForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HomepageBlocksTable::configure($table);
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
            'index' => ListHomepageBlocks::route('/'),
            'create' => CreateHomepageBlock::route('/create'),
            'edit' => EditHomepageBlock::route('/{record}/edit'),
        ];
    }
}
