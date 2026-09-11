<?php

namespace App\Filament\Resources\SellRequests\Pages;

use App\Filament\Resources\SellRequests\SellRequestsResource;
use App\Filament\Resources\SellRequests\Tables\SellRequestsTable;
use App\Services\SellRequestService;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Filament\Notifications\Notification;

class EditSellRequest extends EditRecord
{
    protected static string $resource = SellRequestsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            SellRequestsTable::settleAction(),
            SellRequestsTable::closeBiddingAction(),
            SellRequestsTable::adminCancelAction(),
            Action::make('mark_expired')
                ->label('Mark expired')
                ->icon(Heroicon::OutlinedClock)
                ->color('gray')
                ->visible(fn () => in_array($this->record->status, ['pending_bids', 'bidding'], true))
                ->requiresConfirmation()
                ->action(function () {
                    $record = $this->record;
                    $record->transitionTo(\App\Models\SellRequest::STATUS_EXPIRED, 'expired_by_admin');
                    $this->fillForm();

                    Notification::make()
                        ->title('Request marked as expired.')
                        ->success()
                        ->send();
                }),
        ];
    }
}