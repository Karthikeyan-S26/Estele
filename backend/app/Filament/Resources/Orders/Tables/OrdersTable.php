<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Enums\PaginationMode;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrdersTable
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
                TextColumn::make('order_number')
                    ->label('Order #')
                    ->searchable(),
                TextColumn::make('customer_name')
                    ->searchable(),
                TextColumn::make('customer_email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('total')
                    ->formatStateUsing(fn ($state) => '₹'.number_format((float) $state, 2))
                    ->sortable(),
                // Explicit shorter labels — the auto-generated "Payment method"/
                // "Payment status" headers were wider than the badge content
                // they head (e.g. "razorpay", "paid"), and were the last bit
                // pushing this table past its available width into an
                // unnecessary horizontal scrollbar. "Pay" prefix keeps them
                // distinguishable from the unrelated order-status column.
                TextColumn::make('payment_method')
                    ->label('Pay Method')
                    ->badge(),
                TextColumn::make('payment_status')
                    ->label('Pay Status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'paid' => 'success',
                        'pending' => 'warning',
                        'failed' => 'danger',
                        'refunded' => 'gray',
                        'partially_refunded' => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'placed' => 'info',
                        'accepted' => 'warning',
                        'packed' => 'warning',
                        'shipped' => 'primary',
                        'delivered' => 'success',
                        'cancelled' => 'danger',
                        'returned' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('created_at')
                    ->label('Placed')
                    // Year dropped from the visible column (was the single
                    // widest cell in the table, "27 Jul 2026, 02:24 PM") —
                    // still available on hover via dateTooltip so nothing is
                    // actually lost, just not taking up column width by default.
                    ->dateTime('d M, h:i A')
                    ->dateTooltip('d M Y, h:i A')
                    ->sortable(),
                // Customer-submitted via account.orders.cancellation-request (see
                // AccountController) — a flag for admin review, not a status
                // change; the actual cancel/return transition still happens via
                // the normal EditAction/status field below, guarded by
                // Order::ALLOWED_TRANSITIONS as always.
                IconColumn::make('cancellation_requested_at')
                    ->label('Cancel/Return')
                    ->boolean()
                    ->tooltip(fn (Order $record) => $record->cancellation_requested_at
                        ? "Requested {$record->cancellation_requested_at->format('d M Y')}: {$record->cancellation_reason}"
                        : null),
            ])
            ->filters([
                Filter::make('cancellation_requested')
                    ->label('Has cancellation/return request')
                    ->query(fn (Builder $query) => $query->whereNotNull('cancellation_requested_at')),
                SelectFilter::make('status')
                    ->options([
                        'placed' => 'Placed',
                        'accepted' => 'Accepted',
                        'packed' => 'Packed',
                        'shipped' => 'Shipped',
                        'delivered' => 'Delivered',
                        'cancelled' => 'Cancelled',
                        'returned' => 'Returned',
                    ]),
                SelectFilter::make('payment_status')
                    ->options([
                        'pending' => 'Pending',
                        'paid' => 'Paid',
                        'failed' => 'Failed',
                        'refunded' => 'Refunded',
                        'partially_refunded' => 'Partially Refunded',
                    ]),
            ])
            ->recordActions([
                // Both actions collapsed to icon-only (was label+icon link text) —
                // the labeled version of just these two actions alone was 161px
                // wide, the single biggest contributor to the table's own content
                // area (1298px) overflowing its 1137px column area and forcing an
                // unnecessary horizontal scrollbar under every Orders list.
                // Tooltip keeps the action identifiable without the text.
                Action::make('invoice')
                    ->label('Invoice')
                    ->icon(Heroicon::OutlinedDocumentArrowDown)
                    ->color('gray')
                    ->iconButton()
                    ->tooltip('Download invoice')
                    // Only once the admin has accepted the order — matches the
                    // "accepted" transition that also triggers the customer email.
                    ->visible(fn (Order $record) => $record->status !== 'placed')
                    ->action(fn (Order $record) => self::streamInvoice($record)),
                EditAction::make()
                    ->iconButton()
                    ->tooltip('Edit'),
            ]);
    }

    public static function streamInvoice(Order $record)
    {
        $record->loadMissing('items');

        return response()->streamDownload(function () use ($record) {
            echo Pdf::loadView('orders.invoice', ['order' => $record])->output();
        }, "invoice-{$record->order_number}.pdf");
    }
}
