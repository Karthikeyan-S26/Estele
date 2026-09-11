<?php

namespace App\Filament\Resources\SellRequests\Schemas;

use App\Models\SellRequest;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SellRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Request')
                    ->columns(2)
                    ->schema([
                        TextInput::make('request_number')
                            ->disabled(),
                        Select::make('user_id')
                            ->label('Customer')
                            ->relationship('user', 'name')
                            ->disabled(),
                        Select::make('status')
                            ->options(fn () => collect(SellRequest::ALLOWED_TRANSITIONS)
                                ->keys()
                                ->mapWithKeys(fn (string $status) => [$status => ucwords(str_replace('_', ' ', $status))])
                                ->toArray())
                            ->disabled(),
                        TextInput::make('item_type')
                            ->disabled()
                            ->formatStateUsing(fn (string $state) => ucfirst($state)),
                        TextInput::make('city')
                            ->disabled()
                            ->placeholder('—'),
                        TextInput::make('contact_phone')
                            ->disabled(),
                        Textarea::make('description')
                            ->disabled()
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

                Section::make('Bidding')
                    ->columns(2)
                    ->schema([
                        TextInput::make('bids_start_at')
                            ->label('Bidding opened')
                            ->disabled()
                            ->date(),
                        TextInput::make('bids_end_at')
                            ->label('Bidding closes')
                            ->disabled()
                            ->date(),
                        TextInput::make('highest_bid_amount')
                            ->label('Highest bid')
                            ->disabled()
                            ->prefix('₹')
                            ->placeholder('—'),
                        TextInput::make('winning_bid_id')
                            ->label('Winning bid')
                            ->disabled()
                            ->placeholder('—'),
                        TextInput::make('result_selected_at')
                            ->label('Winner resolved')
                            ->disabled()
                            ->date()
                            ->placeholder('—'),
                    ]),

                Section::make('Settlement')
                    ->columns(2)
                    ->schema([
                        TextInput::make('admin_valuation')
                            ->label('Admin valuation')
                            ->disabled()
                            ->prefix('₹')
                            ->placeholder('—'),
                        TextInput::make('deduction_amount')
                            ->label('Platform deduction (10%)')
                            ->disabled()
                            ->prefix('₹')
                            ->placeholder('—'),
                        TextInput::make('wallet_credit')
                            ->label('Wallet credit (90%)')
                            ->disabled()
                            ->prefix('₹')
                            ->placeholder('—'),
                        TextInput::make('wallet_transaction_id')
                            ->label('Wallet transaction')
                            ->disabled()
                            ->placeholder('—'),
                        TextInput::make('settlement_at')
                            ->label('Settled')
                            ->disabled()
                            ->date()
                            ->placeholder('—'),
                        TextInput::make('cancel_reason')
                            ->label('Cancelled')
                            ->disabled()
                            ->placeholder('—'),
                    ]),
            ]);
    }
}