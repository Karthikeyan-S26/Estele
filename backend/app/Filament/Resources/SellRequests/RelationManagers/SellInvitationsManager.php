<?php

namespace App\Filament\Resources\SellRequests\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\PaginationMode;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SellInvitationsManager extends RelationManager
{
    protected static string $relationship = 'invitations';

    protected static ?string $title = 'Vendor invitations';

    public function table(Table $table): Table
    {
        return $table
            ->paginationMode(PaginationMode::Simple)
            ->defaultSort('sent_at', 'desc')
            ->columns([
                TextColumn::make('vendor.name')
                    ->label('Vendor')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'accepted' => 'success',
                        'declined' => 'danger',
                        'expired' => 'gray',
                        default => 'warning',
                    }),
                TextColumn::make('sent_at')
                    ->dateTime('d M, h:i A'),
                TextColumn::make('responded_at')
                    ->dateTime('d M, h:i A')
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'accepted' => 'Accepted',
                        'declined' => 'Declined',
                        'expired' => 'Expired',
                    ]),
            ])
            ->recordActions([]);
    }
}