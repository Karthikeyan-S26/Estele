<?php

namespace App\Filament\Resources\Settings\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class SettingForm
{
    /**
     * Keys with a fixed set of valid values get a Select instead of free
     * text — a typo here (e.g. "Shiprocket" vs "shiprocket") wouldn't error,
     * it would just silently no-op the setting with no warning anywhere
     * (App\Services\Shipping\ShippingManager does an exact string match).
     */
    private const ENUM_OPTIONS = [
        'shipping_provider' => [
            'flat' => 'Flat rate (default — no external account needed)',
            'shiprocket' => 'Shiprocket (requires SHIPROCKET_EMAIL/PASSWORD in .env)',
        ],
        'payment_provider' => [
            'cod' => 'Cash on Delivery only (default)',
            'razorpay' => 'Razorpay (requires RAZORPAY_KEY_ID/KEY_SECRET in .env)',
        ],
    ];

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('key')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->live(),

                Select::make('value')
                    ->label('Value')
                    ->options(fn (Get $get) => self::ENUM_OPTIONS[(string) $get('key')] ?? [])
                    ->visible(fn (Get $get) => self::isEnumKey($get('key')))
                    ->dehydrated(fn (Get $get) => self::isEnumKey($get('key')))
                    ->required(),

                Textarea::make('value')
                    ->label('Value')
                    ->columnSpanFull()
                    ->visible(fn (Get $get) => ! self::isEnumKey($get('key')))
                    ->dehydrated(fn (Get $get) => ! self::isEnumKey($get('key'))),
            ]);
    }

    private static function isEnumKey(?string $key): bool
    {
        return array_key_exists((string) $key, self::ENUM_OPTIONS);
    }
}
