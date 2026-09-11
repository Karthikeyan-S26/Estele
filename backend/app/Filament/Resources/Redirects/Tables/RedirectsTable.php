<?php

namespace App\Filament\Resources\Redirects\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Enums\PaginationMode;
use Filament\Tables\Table;

class RedirectsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->paginationMode(PaginationMode::Simple)
            ->disabledSelection(! extension_loaded('intl'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('old_path')
                    ->label('Old path')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('new_path')
                    ->label('New destination')
                    ->searchable()
                    ->limit(50),
                TextColumn::make('status_code')
                    ->label('Type')
                    ->badge(),
                IconColumn::make('is_active')
                    ->boolean(),
                TextColumn::make('source')
                    ->badge()
                    ->color(fn (string $state) => $state === 'auto' ? 'gray' : 'success'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active'),
                SelectFilter::make('source')->options([
                    'auto' => 'Auto (slug change)',
                    'manual' => 'Manual',
                ]),
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
