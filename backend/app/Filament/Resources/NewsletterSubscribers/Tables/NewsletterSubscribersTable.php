<?php

namespace App\Filament\Resources\NewsletterSubscribers\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\PaginationMode;
use Filament\Tables\Table;

class NewsletterSubscribersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->paginationMode(PaginationMode::Simple)
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('email')
                    ->searchable(),
                TextColumn::make('source')
                    ->badge(),
                TextColumn::make('popup.name')
                    ->label('Popup')
                    ->placeholder('—'),
                TextColumn::make('created_at')
                    ->label('Subscribed')
                    ->dateTime('d M Y, h:i A')
                    ->sortable(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
