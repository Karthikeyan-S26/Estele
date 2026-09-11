<?php

namespace App\Filament\Resources\Reviews\Pages;

use App\Filament\Resources\Reviews\ReviewResource;
use Filament\Resources\Pages\CreateRecord;

class CreateReview extends CreateRecord
{
    protected static string $resource = ReviewResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // The form collects a "Visible" toggle (see ReviewForm), not the
        // status string the pending/approved/rejected moderation flow uses —
        // this is the one place that maps between them for an admin-authored
        // review, same as approved/rejected already mean "visible on the
        // storefront" / "not" for customer-submitted ones.
        $data['status'] = ($data['is_visible'] ?? true) ? 'approved' : 'pending';
        unset($data['is_visible']);

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        // FileUpload::make('photo') is dehydrated(false) (see ReviewForm) so
        // it never touches the reviews table — it lands in this page's raw
        // form state instead, keyed the same as the field name.
        $path = $this->form->getRawState()['photo'] ?? null;

        if (! $path) {
            return;
        }

        $disk = \Illuminate\Support\Facades\Storage::disk('original_images');

        if (! $disk->exists($path)) {
            return;
        }

        $this->record
            ->addMedia($disk->path($path))
            ->preservingOriginal()
            ->toMediaCollection('photos');
    }
}
