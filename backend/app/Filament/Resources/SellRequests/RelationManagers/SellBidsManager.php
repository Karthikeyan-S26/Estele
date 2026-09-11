<?php

namespace App\Filament\Resources\SellRequests\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\PaginationMode;
use Filament\Tables\Table;

class SellBidsManager extends RelationManager
{
    protected static string $relationship = 'bids';

    protected static ?string $title = 'Bids';

    public function table(Table $table): Table
    {
        return $table
            ->paginationMode(PaginationMode::Simple)
            ->columns([
                TextColumn::make('vendor.name')
                    ->label('Vendor')
                    ->searchable(),
                TextColumn::make('amount')
                    ->formatStateUsing(fn ($state) => '₹'.number_format((float) $state, 0))
                    ->sortable(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('submitted_at')
                    ->dateTime('d M Y, h:i A')
                    ->sortable(),
            ])
            ->recordActions([]);
    }
}