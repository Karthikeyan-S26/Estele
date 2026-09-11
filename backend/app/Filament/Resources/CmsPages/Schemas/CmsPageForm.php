<?php

namespace App\Filament\Resources\CmsPages\Schemas;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CmsPageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->alphaDash()
                    ->unique(ignoreRecord: true),
                Select::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'published' => 'Published',
                    ])
                    ->required()
                    ->default('draft'),
                RichEditor::make('content')
                    ->required()
                    ->columnSpanFull(),

                Section::make('SEO')
                    ->description('Optional — leave blank to use the site defaults.')
                    ->relationship('seoMeta')
                    ->schema([
                        TextInput::make('title')
                            ->label('Meta title'),
                        Textarea::make('description')
                            ->label('Meta description'),
                        TextInput::make('og_image')
                            ->label('OG image URL'),
                        TextInput::make('canonical')
                            ->label('Canonical URL'),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->columnSpanFull(),
            ]);
    }
}
