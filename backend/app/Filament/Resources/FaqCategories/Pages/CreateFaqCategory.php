<?php

namespace App\Filament\Resources\FaqCategories\Pages;

use App\Filament\Resources\FaqCategories\FaqCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFaqCategory extends CreateRecord
{
    protected static string $resource = FaqCategoryResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
