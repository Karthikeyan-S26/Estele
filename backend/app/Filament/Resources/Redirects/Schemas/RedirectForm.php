<?php

namespace App\Filament\Resources\Redirects\Schemas;

use App\Models\Redirect;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class RedirectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('old_path')
                    ->label('Old URL path')
                    ->placeholder('/old-product-slug')
                    ->helperText('The path that should now redirect — relative to the site root, e.g. "/products/old-slug".')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->different('new_path')
                    ->validationMessages([
                        'different' => 'Old path and new path can\'t be the same — that would redirect a page to itself.',
                    ])
                    ->dehydrateStateUsing(fn (?string $state) => $state ? Redirect::normalizePath($state) : $state),

                TextInput::make('new_path')
                    ->label('New URL / destination')
                    ->placeholder('/products/new-slug or https://example.com/…')
                    ->helperText('Where visitors should land instead — a relative path or a full URL.')
                    ->required()
                    ->maxLength(2048)
                    ->dehydrateStateUsing(fn (?string $state) => $state && ! preg_match('#^https?://#i', $state)
                        ? Redirect::normalizePath($state)
                        : $state),

                Select::make('status_code')
                    ->label('Redirect type')
                    ->options([
                        301 => '301 — Permanent',
                        302 => '302 — Temporary',
                    ])
                    ->default(301)
                    ->required(),

                Toggle::make('is_active')
                    ->default(true)
                    ->required(),
            ]);
    }
}
