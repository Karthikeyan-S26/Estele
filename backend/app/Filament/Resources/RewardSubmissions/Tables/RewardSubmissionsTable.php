<?php

namespace App\Filament\Resources\RewardSubmissions\Tables;

use App\Mail\RewardSubmissionRejected;
use App\Models\RewardSubmission;
use App\Services\WalletService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\PaginationMode;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class RewardSubmissionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->paginationMode(PaginationMode::Simple)
            ->defaultSort('created_at', 'desc')
            ->columns([
                SpatieMediaLibraryImageColumn::make('image')
                    ->collection('image')
                    ->conversion('thumb')
                    ->circular(),
                TextColumn::make('user.name')
                    ->label('Customer')
                    ->searchable(),
                TextColumn::make('order.order_number')
                    ->label('Order')
                    ->searchable()
                    // Narrowest useful column on a phone-width table — the
                    // customer/status/amount columns carry the essential
                    // "what happened" story; order number is a lookup detail.
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'warning',
                    }),
                TextColumn::make('reward_amount')
                    ->label('Reward')
                    ->formatStateUsing(fn ($state) => filled($state) ? '₹'.number_format((float) $state, 2) : null),
                TextColumn::make('reviewer.name')
                    ->label('Reviewed By')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Submitted')
                    ->dateTime('d M Y, h:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('reviewed_at')
                    ->label('Reviewed')
                    ->dateTime('d M Y, h:i A')
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ]),
            ])
            ->recordActions([
                self::approveAction(),
                self::rejectAction(),
                EditAction::make(),
            ]);
    }

    public static function approveAction(): Action
    {
        return Action::make('approve')
            ->label('Approve')
            ->icon(Heroicon::OutlinedCheckCircle)
            ->color('success')
            ->visible(fn (RewardSubmission $record) => $record->status === 'pending')
            ->schema([
                TextInput::make('reward_amount')
                    ->label('Reward amount (₹)')
                    ->numeric()
                    ->required()
                    ->minValue(0.01),
            ])
            ->action(function (array $data, RewardSubmission $record) {
                DB::transaction(function () use ($data, $record) {
                    $updated = RewardSubmission::whereKey($record->id)
                        ->where('status', 'pending')
                        ->update([
                            'status' => 'approved',
                            'reward_amount' => $data['reward_amount'],
                            'reviewed_at' => now(),
                            'reviewed_by' => auth()->id(),
                        ]);

                    if ($updated === 0) {
                        // Already reviewed by a concurrent request — no-op, no error.
                        return;
                    }

                    app(WalletService::class)->credit(
                        $record->user,
                        (float) $data['reward_amount'],
                        'reward_approved',
                        $record->fresh(),
                    );
                });
            });
    }

    public static function rejectAction(): Action
    {
        return Action::make('reject')
            ->label('Reject')
            ->icon(Heroicon::OutlinedXCircle)
            ->color('danger')
            ->visible(fn (RewardSubmission $record) => $record->status === 'pending')
            ->schema([
                Textarea::make('rejection_reason')
                    ->label('Reason')
                    ->required(),
            ])
            ->action(function (array $data, RewardSubmission $record) {
                DB::transaction(function () use ($data, $record) {
                    $updated = RewardSubmission::whereKey($record->id)
                        ->where('status', 'pending')
                        ->update([
                            'status' => 'rejected',
                            'rejection_reason' => $data['rejection_reason'],
                            'reviewed_at' => now(),
                            'reviewed_by' => auth()->id(),
                        ]);

                    if ($updated === 0) {
                        // Already reviewed by a concurrent request — no-op, no error.
                        return;
                    }

                    Mail::to($record->user->email)->queue(new RewardSubmissionRejected($record->fresh()));
                });
            });
    }
}
