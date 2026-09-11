<?php

namespace App\Filament\Resources\SellRequests\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\PaginationMode;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SellAuditLogsManager extends RelationManager
{
    protected static string $relationship = 'auditLogs';

    protected static ?string $title = 'Audit trail';

    public function table(Table $table): Table
    {
        return $table
            ->paginationMode(PaginationMode::Simple)
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('event')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('from_status')
                    ->label('From')
                    ->placeholder('—')
                    ->formatStateUsing(fn (?string $state) => filled($state) ? ucwords(str_replace('_', ' ', $state)) : null),
                TextColumn::make('to_status')
                    ->label('To')
                    ->placeholder('—')
                    ->formatStateUsing(fn (?string $state) => filled($state) ? ucwords(str_replace('_', ' ', $state)) : null),
                TextColumn::make('metadata')
                    ->formatStateUsing(fn (?array $state) => $state ? json_encode($state) : null)
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('actor.name')
                    ->label('Actor'),
                TextColumn::make('created_at')
                    ->dateTime('d M Y, h:i A')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('event')
                    ->options([
                        'created' => 'Created',
                        'vendor_invited' => 'Vendor invited',
                        'vendor_accepted' => 'Vendor accepted',
                        'vendor_declined' => 'Vendor declined',
                        'bid_submitted' => 'Bid submitted',
                        'bid_updated' => 'Bid updated',
                        'bidding_opened' => 'Bidding opened',
                        'bids_closed' => 'Bids closed',
                        'bids_closed_no_bids' => 'Bids closed (no bids)',
                        'settlement_completed' => 'Settlement completed',
                        'cancelled_by_customer' => 'Cancelled by customer',
                        'cancelled_by_admin' => 'Cancelled by admin',
                        'expired_by_admin' => 'Expired by admin',
                    ]),
            ])
            ->recordActions([]);
    }
}