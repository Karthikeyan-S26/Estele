<?php

namespace App\Filament\Resources\WalletManagement\Tables;

use App\Models\User;
use App\Services\WalletService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\PaginationMode;
use Filament\Tables\Table;

class WalletManagementTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->paginationMode(PaginationMode::Simple)
            ->query(fn () => User::query()->whereDoesntHave('roles'))
            ->defaultSort('wallet_balance', 'desc')
            ->searchable()
            ->columns([
                TextColumn::make('name')
                    ->label('Customer')
                    ->searchable(),
                TextColumn::make('email')
                    ->searchable(),
                TextColumn::make('wallet_balance')
                    ->label('Balance')
                    ->formatStateUsing(fn ($state) => '₹'.number_format((float) $state, 2))
                    ->sortable(),
                TextColumn::make('total_credit')
                    ->label('Total Credit')
                    ->state(fn (User $record) => $record->walletTransactions()->where('type', 'credit')->sum('amount'))
                    ->formatStateUsing(fn ($state) => '₹'.number_format((float) $state, 2)),
                TextColumn::make('total_debit')
                    ->label('Total Debit')
                    ->state(fn (User $record) => $record->walletTransactions()->where('type', 'debit')->sum('amount'))
                    ->formatStateUsing(fn ($state) => '₹'.number_format((float) $state, 2)),
            ])
            ->recordActions([
                self::historyAction(),
                self::creditAction(),
                self::debitAction(),
            ]);
    }

    public static function historyAction(): Action
    {
        return Action::make('history')
            ->label('History')
            ->icon(Heroicon::OutlinedClock)
            ->color('gray')
            ->modalHeading(fn (User $record) => "Wallet history — {$record->name}")
            ->modalContent(fn (User $record) => view('filament.wallet-management.history', [
                'transactions' => $record->walletTransactions()->latest()->limit(50)->get(),
            ]))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Close');
    }

    public static function creditAction(): Action
    {
        return Action::make('credit')
            ->label('Credit')
            ->icon(Heroicon::OutlinedPlusCircle)
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading(fn (User $record) => "Credit wallet — {$record->name}")
            ->schema([
                TextInput::make('amount')
                    ->label('Amount (₹)')
                    ->numeric()
                    ->required()
                    ->minValue(0.01),
                Textarea::make('reason')
                    ->label('Reason')
                    ->required(),
            ])
            ->action(function (array $data, User $record) {
                app(WalletService::class)->credit(
                    $record,
                    (float) $data['amount'],
                    'admin_credit: '.$data['reason'],
                );

                Notification::make()
                    ->title('Wallet credited.')
                    ->success()
                    ->send();
            });
    }

    public static function debitAction(): Action
    {
        return Action::make('debit')
            ->label('Debit')
            ->icon(Heroicon::OutlinedMinusCircle)
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading(fn (User $record) => "Debit wallet — {$record->name}")
            ->schema([
                TextInput::make('amount')
                    ->label('Amount (₹)')
                    ->numeric()
                    ->required()
                    ->minValue(0.01),
                Textarea::make('reason')
                    ->label('Reason')
                    ->required(),
            ])
            ->action(function (array $data, User $record) {
                try {
                    app(WalletService::class)->debit(
                        $record,
                        (float) $data['amount'],
                        'admin_debit: '.$data['reason'],
                    );
                } catch (\DomainException $e) {
                    Notification::make()
                        ->title($e->getMessage())
                        ->danger()
                        ->send();

                    return;
                }

                Notification::make()
                    ->title('Wallet debited.')
                    ->success()
                    ->send();
            });
    }
}
