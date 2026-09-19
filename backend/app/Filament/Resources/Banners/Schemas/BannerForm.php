<?php

namespace App\Filament\Resources\Banners\Schemas;

use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class BannerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->maxLength(255)
                    ->helperText('Internal label only — not shown on the site.'),
                TextInput::make('link_url')
                    ->label('Link URL')
                    ->maxLength(255)
                    ->helperText('e.g. /collections/necklace-sets'),
                TextInput::make('sort_order')
                    ->required()
                    ->numeric()
                    ->integer()
                    ->minValue(0)
                    ->default(0),
                Toggle::make('is_active')
                    ->required()
                    ->default(true),
                SpatieMediaLibraryFileUpload::make('image')
                    ->label('Desktop Banner')
                    ->collection('image')
                    ->conversion('desktop')
                    ->image()
                    ->maxSize(1024) // 1MB
                    ->helperText('Shown strictly on tablet/desktop widths (Recommended size: 1800x700px).')
                    ->columnSpanFull(),
                SpatieMediaLibraryFileUpload::make('mobile_image')
                    ->label('Mobile Banner')
                    ->collection('mobile_image')
                    ->conversion('mobile')
                    ->image()
                    ->maxSize(1024) // 1MB
                    ->helperText('Shown strictly on mobile widths. Recommended size: 750x1000px (or similar 3:4 portrait ratio) to naturally cover 50% of the mobile screen.')
                    ->columnSpanFull(),
                TextInput::make('image_alt_text')
                    ->label('Image alt text')
                    ->required()
                    ->maxLength(255)
                    ->helperText('Shown publicly to screen readers / image search — the "Title" field above is internal-only.')
                    ->columnSpanFull(),
            ]);
    }
}
