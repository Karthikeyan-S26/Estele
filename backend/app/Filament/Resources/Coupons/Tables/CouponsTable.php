<?php

namespace App\Filament\Resources\Coupons\Tables;

use App\Models\Coupon;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Enums\PaginationMode;
use Filament\Tables\Table;

class CouponsTable
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
                TextColumn::make('code')
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('type')
                    ->badge(),
                TextColumn::make('value')
                    ->label('Value')
                    ->formatStateUsing(fn (Coupon $record) => $record->type === 'percent'
                        ? rtrim(rtrim(number_format((float) $record->value, 2), '0'), '.').'%'
                        : '₹'.number_format((float) $record->value, 0)),
                TextColumn::make('usage')
                    ->label('Used')
                    ->state(fn (Coupon $record) => $record->used_count.' / '.($record->usage_limit ?? '∞')),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->state(fn (Coupon $record) => self::statusFor($record))
                    ->color(fn (string $state) => match ($state) {
                        'Active' => 'success',
                        'Scheduled' => 'info',
                        'Expired' => 'gray',
                        'Exhausted' => 'warning',
                        default => 'danger',
                    }),
                TextColumn::make('expires_at')
                    ->dateTime('d M Y')
                    ->placeholder('Never')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options(['flat' => 'Flat', 'percent' => 'Percent']),
                SelectFilter::make('is_active')
                    ->label('Active')
                    ->options([1 => 'Active', 0 => 'Disabled']),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    private static function statusFor(Coupon $record): string
    {
        if (! $record->is_active) {
            return 'Disabled';
        }
        if ($record->starts_at && now()->lt($record->starts_at)) {
            return 'Scheduled';
        }
        if ($record->expires_at && now()->gt($record->expires_at)) {
            return 'Expired';
        }
        if ($record->usage_limit !== null && $record->used_count >= $record->usage_limit) {
            return 'Exhausted';
        }

        return 'Active';
    }
}
