<?php

namespace App\Filament\Resources\HomepageBlocks\Schemas;

use App\Models\Blog;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Faq;
use App\Models\Product;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class HomepageBlockForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('type')
                    ->required()
                    ->options([
                        'shop_by_category' => 'Shop by Category',
                        'collection_banner' => 'Collection banner (split hero)',
                        'product_carousel' => 'Product grid',
                        'collection_carousel' => 'Collection grid',
                        'price_tiers' => 'Price tiers (Your Budget, Your Bling)',
                        'celebrities' => 'Celebrities / As Seen On',
                        'promo_banner' => 'Promo banner grid',
                        'usp' => 'USP / trust badges',
                        'testimonials' => 'Testimonials',
                        'journal' => 'Journal / blog posts',
                        'shop_the_look' => 'Shop the look (Instagram grid)',
                        'brand_story' => 'Brand story + stats',
                        'faq' => 'FAQ accordion',
                        'newsletter' => 'Newsletter signup',
                    ])
                    ->native(false),
                TextInput::make('title')
                    ->maxLength(255),
                TextInput::make('subtitle')
                    ->maxLength(255),
                TextInput::make('cta_label')
                    ->label('CTA label')
                    ->maxLength(255),
                TextInput::make('cta_url')
                    ->label('CTA URL')
                    ->maxLength(255),
                TextInput::make('sort_order')
                    ->required()
                    ->numeric()
                    ->integer()
                    ->minValue(0)
                    ->default(0),
                Toggle::make('is_active')
                    ->required()
                    ->default(true),

                Section::make('Items')
                    ->description('The rows this section renders. What each field means depends on the section type: Product grid / Shop the look → link each item to a Product; Collection grid → a Collection; Journal → a Blog post (leave "Link to" empty on one item to make it the dark promo card); FAQ → an FAQ; Price tiers → Title is the label ("Under"), Body is the amount ("₹999"), Link URL is where it goes (the last item renders as the dark premium tile); USP / brand story stats → Title and Body.')
                    ->schema([
                        Repeater::make('items')
                            ->relationship('items')
                            ->orderColumn('sort_order')
                            ->schema([
                                TextInput::make('title')
                                    ->maxLength(255),
                                Textarea::make('body')
                                    ->rows(2),
                                TextInput::make('link_url')
                                    ->label('Link URL')
                                    ->maxLength(255),
                                TextInput::make('rating')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(5)
                                    ->helperText('1-5, for testimonials only'),
                                Select::make('itemable_type')
                                    ->label('Link to')
                                    ->options([
                                        Product::class => 'Product',
                                        Category::class => 'Category',
                                        Collection::class => 'Collection',
                                        Blog::class => 'Blog post',
                                        Faq::class => 'FAQ',
                                    ])
                                    ->native(false)
                                    ->live(),
                                Select::make('itemable_id')
                                    ->label('Record')
                                    ->options(function (\Filament\Schemas\Components\Utilities\Get $get) {
                                        return match ($get('itemable_type')) {
                                            Product::class => Product::query()->pluck('title', 'id'),
                                            Category::class => Category::query()->pluck('name', 'id'),
                                            Collection::class => Collection::query()->pluck('name', 'id'),
                                            Blog::class => Blog::query()->pluck('title', 'id'),
                                            Faq::class => Faq::query()->pluck('question', 'id'),
                                            default => [],
                                        };
                                    })
                                    ->searchable()
                                    ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => filled($get('itemable_type'))),
                                SpatieMediaLibraryFileUpload::make('image')
                                    ->collection('image')
                                    ->conversion('card')
                                    ->image()
                                    ->maxSize(10240) // 10MB - Phase 6 audit, was unlimited
                                    ->columnSpanFull(),
                            ])
                            ->columns(2)
                            ->columnSpanFull()
                            ->collapsible(),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
