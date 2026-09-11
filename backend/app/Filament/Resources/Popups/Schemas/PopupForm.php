<?php

namespace App\Filament\Resources\Popups\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class PopupForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Popup')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Internal label only — not shown on the site.'),
                        Select::make('type')
                            ->required()
                            ->options([
                                'newsletter' => 'Newsletter signup',
                                'free_gift' => 'Free gift banner',
                                'exit_intent' => 'Exit intent',
                            ])
                            ->native(false)
                            ->live()
                            // Only pre-fills sensible defaults on create — doesn't fight
                            // an admin who deliberately wants email collection on a
                            // "free gift" popup, but stops the common mistake of picking
                            // a type and leaving the unrelated fields on their defaults.
                            ->afterStateUpdated(function (string $state, Get $get, \Filament\Schemas\Components\Utilities\Set $set, string $operation) {
                                if ($operation !== 'create') {
                                    return;
                                }

                                $set('show_email_field', $state === 'newsletter');
                                $set('trigger', $state === 'exit_intent' ? 'exit_intent' : 'delay');
                            }),
                        Select::make('trigger')
                            ->required()
                            ->options([
                                'delay' => 'After a delay',
                                'exit_intent' => 'When leaving the page',
                            ])
                            ->native(false)
                            ->live(),
                        TextInput::make('delay_seconds')
                            ->numeric()
                            ->integer()
                            ->minValue(0)
                            ->default(4)
                            ->suffix('seconds')
                            ->visible(fn (Get $get) => $get('trigger') === 'delay'),
                        Toggle::make('show_email_field')
                            ->label('Collect email address')
                            ->helperText('Turn on for a newsletter-style popup.'),
                        Toggle::make('is_active')
                            ->required()
                            ->default(true),
                    ]),

                Section::make('Content')
                    ->columns(2)
                    ->schema([
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Textarea::make('body')
                            ->rows(2)
                            ->columnSpanFull(),
                        TextInput::make('discount_code')
                            ->label('Discount code')
                            ->maxLength(50)
                            ->helperText('Optional — shown to the visitor if set.'),
                        TextInput::make('cta_label')
                            ->label('Button label')
                            ->maxLength(50),
                        TextInput::make('cta_url')
                            ->label('Button link')
                            ->maxLength(255)
                            ->helperText('Optional — leave blank for a newsletter-only popup.'),
                        SpatieMediaLibraryFileUpload::make('image')
                            ->collection('image')
                            ->conversion('card')
                            ->image()
                            ->maxSize(10240) // 10MB - Phase 6 audit, was unlimited
                            ->columnSpanFull(),
                        TextInput::make('image_alt_text')
                            ->label('Image alt text')
                            ->maxLength(255)
                            ->helperText('Optional — falls back to the popup title above if left blank.')
                            ->columnSpanFull(),
                    ]),

                Section::make('Schedule & Targeting')
                    ->columns(2)
                    ->schema([
                        DateTimePicker::make('starts_at')
                            ->live(),
                        DateTimePicker::make('ends_at')
                            ->after('starts_at')
                            ->helperText('Must be after the start date, if both are set.'),
                        Toggle::make('target_new_visitors_only')
                            ->label('Show to new visitors only')
                            ->helperText('Off shows it to every visitor.'),
                        TextInput::make('sort_order')
                            ->required()
                            ->numeric()
                            ->integer()
                            ->minValue(0)
                            ->default(0)
                            ->helperText('If more than one popup is eligible at once, the lowest sort order wins.'),
                    ]),
            ]);
    }
}
