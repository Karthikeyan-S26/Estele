<?php

namespace App\Filament\Resources\Coupons\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class CouponForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Coupon')
                    ->columns(2)
                    ->schema([
                        TextInput::make('code')
                            ->required()
                            ->maxLength(50)
                            ->unique(ignoreRecord: true)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, callable $set) => $set('code', strtoupper(trim((string) $state))))
                            ->helperText('Stored and matched in uppercase.'),
                        Toggle::make('is_active')
                            ->required()
                            ->default(true),
                        Toggle::make('is_public')
                            ->label('Publicly listed')
                            ->helperText('Shown in the storefront\'s "View all coupons" list. Turn off for referral / email-only codes that should still work but not be advertised.')
                            ->default(true),

                        Select::make('type')
                            ->options([
                                'flat' => 'Flat amount (₹)',
                                'percent' => 'Percentage (%)',
                            ])
                            ->required()
                            ->live()
                            ->default('flat'),
                        TextInput::make('value')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->label(fn (Get $get) => $get('type') === 'percent' ? 'Percentage off' : 'Amount off (₹)'),
                        TextInput::make('max_discount_amount')
                            ->label('Max discount (₹)')
                            ->numeric()
                            ->minValue(0)
                            ->helperText('Optional cap on the discount amount for percentage coupons.')
                            ->visible(fn (Get $get) => $get('type') === 'percent'),
                        TextInput::make('min_order_value')
                            ->label('Minimum order value (₹)')
                            ->numeric()
                            ->minValue(0),

                        TextInput::make('usage_limit')
                            ->numeric()
                            ->integer()
                            ->minValue(1)
                            ->helperText('Leave blank for unlimited uses.'),
                        DateTimePicker::make('starts_at'),
                        DateTimePicker::make('expires_at'),
                    ]),

                Section::make('Applies To')
                    ->schema([
                        Select::make('scope')
                            ->options([
                                'all' => 'All products',
                                'products' => 'Specific products',
                                'categories' => 'Specific categories',
                            ])
                            ->required()
                            ->live()
                            ->default('all'),
                        Select::make('products')
                            ->relationship('products', 'title')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->visible(fn (Get $get) => $get('scope') === 'products'),
                        Select::make('categories')
                            ->relationship('categories', 'name')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->visible(fn (Get $get) => $get('scope') === 'categories'),
                    ]),
            ]);
    }
}
