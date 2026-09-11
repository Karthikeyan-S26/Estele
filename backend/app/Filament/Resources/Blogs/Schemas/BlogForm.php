<?php

namespace App\Filament\Resources\Blogs\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class BlogForm
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
                Select::make('blog_category_id')
                    ->label('Category')
                    ->relationship('blogCategory', 'name'),
                TextInput::make('author_name')
                    ->maxLength(255),
                Select::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'published' => 'Published',
                    ])
                    ->required()
                    ->default('draft'),
                DateTimePicker::make('published_at'),
                Toggle::make('is_featured'),
                Textarea::make('excerpt')
                    ->columnSpanFull(),
                RichEditor::make('content')
                    ->required()
                    ->columnSpanFull(),

                SpatieMediaLibraryFileUpload::make('featured_image')
                    ->collection('featured_image')
                    ->conversion('detail')
                    ->image()
                    ->maxSize(10240) // 10MB - Phase 6 audit, was unlimited
                    ->live()
                    ->columnSpanFull(),
                TextInput::make('featured_image_alt_text')
                    ->label('Featured image alt text')
                    ->helperText('Required whenever a featured image is set (spec §4.1).')
                    ->maxLength(255)
                    ->required(fn (Get $get) => filled($get('featured_image')))
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
