<?php

namespace App\Filament\Resources\Settings\Pages;

use App\Filament\Resources\Settings\SettingResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Cache;

class ListSettings extends ListRecords
{
    protected static string $resource = SettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Spec §5's "sitemap regenerate trigger" — sitemap.xml already
            // regenerates on its own (1h cache TTL, see SitemapController),
            // this just lets an admin force it immediately after a bulk
            // content change instead of waiting out the TTL.
            Action::make('regenerateSitemap')
                ->label('Regenerate sitemap now')
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('gray')
                ->requiresConfirmation()
                ->action(function () {
                    Cache::forget('sitemap.urls');

                    Notification::make()
                        ->title('Sitemap cache cleared — it will rebuild on the next request to /sitemap.xml.')
                        ->success()
                        ->send();
                }),
            CreateAction::make(),
        ];
    }
}
