<?php

namespace App\Filament\Resources\Reviews\Pages;

use App\Filament\Resources\Reviews\ReviewResource;
use App\Models\Review;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditReview extends EditRecord
{
    protected static string $resource = ReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->label('Approve')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('success')
                ->visible(fn (Review $record) => $record->status !== 'approved')
                ->action(function (Review $record) {
                    $record->update(['status' => 'approved']);
                    $this->fillForm();
                }),
            Action::make('reject')
                ->label('Reject')
                ->icon(Heroicon::OutlinedXCircle)
                ->color('danger')
                ->visible(fn (Review $record) => $record->status !== 'rejected')
                ->action(function (Review $record) {
                    $record->update(['status' => 'rejected']);
                    $this->fillForm();
                }),
            DeleteAction::make(),
        ];
    }
}
