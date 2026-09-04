<?php

namespace App\Filament\Resources\Products\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\PaginationMode;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->paginationMode(PaginationMode::Simple)
            // Bulk-select checkbox column disabled: its "Select all N records"
            // banner calls Number::format() unconditionally regardless of
            // whether any bulk actions are registered, hard-requiring the intl
            // PHP extension this environment doesn't have. toolbarActions()
            // removal alone doesn't turn off selection in Filament v5 — this
            // does.
            ->disabledSelection()
            ->columns([
                SpatieMediaLibraryImageColumn::make('gallery')
                    ->collection('gallery')
                    ->conversion('card')
                    ->circular(),
                TextColumn::make('title')
                    ->searchable(),
                TextColumn::make('slug')
                    ->searchable(),
                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable(),
                TextColumn::make('categories.name')
                    ->badge()
                    ->label('Categories'),
                TextColumn::make('price')
                    ->formatStateUsing(fn ($state) => '₹'.number_format((float) $state, 2))
                    ->sortable(),
                TextColumn::make('compare_at_price')
                    ->formatStateUsing(fn ($state) => filled($state) ? '₹'.number_format((float) $state, 2) : null)
                    ->sortable(),
                TextColumn::make('stock_quantity')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
