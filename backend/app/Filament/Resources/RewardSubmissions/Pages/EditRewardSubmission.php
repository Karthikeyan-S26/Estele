<?php

namespace App\Filament\Resources\RewardSubmissions\Pages;

use App\Filament\Resources\RewardSubmissions\RewardSubmissionResource;
use App\Filament\Resources\RewardSubmissions\Tables\RewardSubmissionsTable;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRewardSubmission extends EditRecord
{
    protected static string $resource = RewardSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            RewardSubmissionsTable::approveAction(),
            RewardSubmissionsTable::rejectAction(),
            DeleteAction::make(),
        ];
    }
}
