<?php

namespace App\Filament\Resources\Popups\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Enums\PaginationMode;
use Filament\Tables\Table;

class PopupsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->paginationMode(PaginationMode::Simple)
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('type')
                    ->badge(),
                TextColumn::make('trigger'),
                IconColumn::make('is_active')
                    ->boolean(),
                TextColumn::make('starts_at')
                    ->dateTime('d M Y')
                    ->placeholder('Anytime'),
                TextColumn::make('ends_at')
                    ->dateTime('d M Y')
                    ->placeholder('No end'),
                IconColumn::make('target_new_visitors_only')
                    ->label('New visitors only')
                    ->boolean(),
                TextColumn::make('sort_order')
                    ->numeric()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        'newsletter' => 'Newsletter signup',
                        'free_gift' => 'Free gift banner',
                        'exit_intent' => 'Exit intent',
                    ]),
                TernaryFilter::make('is_active'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
