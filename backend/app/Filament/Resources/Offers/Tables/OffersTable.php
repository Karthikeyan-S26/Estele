<?php

namespace App\Filament\Resources\Offers\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\PaginationMode;
use Filament\Tables\Table;

class OffersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->paginationMode(PaginationMode::Simple)
            ->disabledSelection(! extension_loaded('intl'))
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('text')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('sort_order')
                    ->when(extension_loaded('intl'), fn (TextColumn $column) => $column->numeric())
                    ->sortable(),
                IconColumn::make('is_active')
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions(extension_loaded('intl') ? [
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ] : []);
    }
}
