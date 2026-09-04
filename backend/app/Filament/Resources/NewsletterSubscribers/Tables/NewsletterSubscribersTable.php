<?php

namespace App\Filament\Resources\NewsletterSubscribers\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\PaginationMode;
use Filament\Tables\Table;

class NewsletterSubscribersTable
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
            ]);
    }
}
