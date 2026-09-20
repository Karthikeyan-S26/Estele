<?php

namespace App\Filament\Resources\FaqCategories\Pages;

use App\Filament\Resources\FaqCategories\FaqCategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditFaqCategory extends EditRecord
{
    protected static string $resource = FaqCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
