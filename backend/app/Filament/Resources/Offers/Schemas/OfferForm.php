<?php

namespace App\Filament\Resources\Offers\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class OfferForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('text')
                    ->label('Offer text')
                    ->required()
                    ->maxLength(255)
                    ->helperText('E.g. "Flat 50% off sitewide" or "Free gift on orders above ₹1,499".')
                    ->columnSpanFull(),
                TextInput::make('sort_order')
                    ->required()
                    ->numeric()
                    ->integer()
                    ->minValue(0)
                    ->default(0),
                Toggle::make('is_active')
                    ->required()
                    ->default(true),
            ]);
    }
}
