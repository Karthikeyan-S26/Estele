<?php

namespace App\Filament\Resources\RewardSubmissions\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RewardSubmissionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Submission')
                    ->columns(2)
                    ->schema([
                        Select::make('user_id')
                            ->label('Customer')
                            ->relationship('user', 'name')
                            ->disabled(),
                        Select::make('order_id')
                            ->label('Order')
                            ->relationship('order', 'order_number')
                            ->disabled(),
                        Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'approved' => 'Approved',
                                'rejected' => 'Rejected',
                            ])
                            ->disabled(),
                        TextInput::make('reward_amount')
                            ->label('Reward amount')
                            ->numeric()
                            ->prefix('₹')
                            ->disabled(),
                        Textarea::make('rejection_reason')
                            ->disabled()
                            ->visible(fn (?string $state) => filled($state))
                            ->columnSpanFull(),
                    ]),

                Section::make('Proof')
                    ->columns(2)
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('image')
                            ->collection('image')
                            ->conversion('thumb')
                            ->disabled(),
                        SpatieMediaLibraryFileUpload::make('video')
                            ->collection('video')
                            ->disabled(),
                    ]),
            ]);
    }
}
