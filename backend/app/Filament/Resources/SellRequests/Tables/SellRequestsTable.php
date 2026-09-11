<?php

namespace App\Filament\Resources\SellRequests\Tables;

use App\Models\SellRequest;
use App\Services\SellRequestService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\PaginationMode;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SellRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->paginationMode(PaginationMode::Simple)
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('request_number')
                    ->label('Request')
                    ->searchable(),
                TextColumn::make('user.name')
                    ->label('Customer')
                    ->searchable(),
                TextColumn::make('item_type')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => ucfirst($state)),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'pending_bids' => 'gray',
                        'bidding' => 'warning',
                        'valuation_review' => 'info',
                        'completed' => 'success',
                        'expired' => 'gray',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => ucwords(str_replace('_', ' ', $state))),
                TextColumn::make('active_bids_count')
                    ->label('Bids')
                    ->state(fn (SellRequest $record) => $record->activeBids()->count()),
                TextColumn::make('highest_bid_amount')
                    ->label('Highest bid')
                    ->placeholder('—')
                    ->formatStateUsing(fn ($state) => filled($state) ? '₹'.number_format((float) $state, 0) : null),
                TextColumn::make('bids_end_at')
                    ->label('Closes')
                    ->dateTime('d M, h:i A')
                    ->placeholder('—'),
                TextColumn::make('created_at')
                    ->label('Posted')
                    ->dateTime('d M Y, h:i A')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending_bids' => 'Pending bids',
                        'bidding' => 'Bidding',
                        'valuation_review' => 'Valuation review',
                        'completed' => 'Completed',
                        'expired' => 'Expired',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
                self::closeBiddingAction(),
                self::settleAction(),
                self::adminCancelAction(),
            ]);
    }

    public static function closeBiddingAction(): Action
    {
        return Action::make('closeBidding')
            ->label('Close bidding')
            ->icon(Heroicon::OutlinedLockClosed)
            ->color('warning')
            ->visible(fn (SellRequest $record) => $record->status === SellRequest::STATUS_BIDDING)
            ->action(function (SellRequest $record) {
                $service = app(SellRequestService::class);
                $service->closeBidding($record);
                $record->refresh();

                $message = $record->status === SellRequest::STATUS_VALUATION_REVIEW
                    ? 'Bidding closed — winner resolved, valuation pending.'
                    : 'Bidding closed with no bids — request expired.';

                Notification::make()
                    ->title($message)
                    ->success()
                    ->send();
            });
    }

    public static function settleAction(): Action
    {
        return Action::make('settle')
            ->label('Settle')
            ->icon(Heroicon::OutlinedBanknotes)
            ->color('success')
            ->visible(fn (SellRequest $record) => $record->status === SellRequest::STATUS_VALUATION_REVIEW)
            ->modalDescription('90% of the valuation is credited to the customer\'s wallet (10% platform deduction), valid for 10 days. Settlement is final.')
            ->schema([
                TextInput::make('admin_valuation')
                    ->label('Admin valuation (₹)')
                    ->numeric()
                    ->required()
                    ->minValue(0),
            ])
            ->action(function (array $data, SellRequest $record) {
                $settled = app(SellRequestService::class)->settle($record, (float) $data['admin_valuation']);
                $record->refresh();

                Notification::make()
                    ->title($settled
                        ? 'Settled — ₹'.number_format((float) $record->wallet_credit, 2).' credited to the customer\'s wallet.'
                        : 'This request is already settled or no longer in valuation review.')
                    ->success()
                    ->send();
            });
    }

    public static function adminCancelAction(): Action
    {
        return Action::make('adminCancel')
            ->label('Cancel')
            ->icon(Heroicon::OutlinedXCircle)
            ->color('danger')
            ->visible(fn (SellRequest $record) => in_array($record->status, [
                SellRequest::STATUS_PENDING_BIDS,
                SellRequest::STATUS_BIDDING,
            ], true))
            ->schema([
                Textarea::make('reason')
                    ->label('Reason')
                    ->required()
                    ->maxLength(255),
            ])
            ->action(function (array $data, SellRequest $record) {
                $cancelled = app(SellRequestService::class)->adminCancel($record, $data['reason'], auth()->user());

                Notification::make()
                    ->title($cancelled ? 'Request cancelled.' : 'This request can no longer be cancelled.')
                    ->success()
                    ->send();
            });
    }
}