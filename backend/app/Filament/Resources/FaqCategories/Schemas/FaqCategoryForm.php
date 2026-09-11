<?php

namespace App\Filament\Resources\FaqCategories\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class FaqCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('sort_order')
                    ->required()
                    ->numeric()
                    ->integer()
                    ->minValue(0)
                    ->default(0),
            ]);
    }
}
