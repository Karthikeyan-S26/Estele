<?php

namespace App\Filament\Resources\Staff\Pages;

use App\Filament\Resources\Staff\StaffResource;
use App\Models\User;
use App\Services\Staff\StaffAccessService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListStaff extends ListRecords
{
    protected static string $resource = StaffResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('add_staff')
                ->label('Add staff member')
                ->icon(Heroicon::OutlinedUserPlus)
                ->visible(fn () => StaffResource::canCreate())
                ->modalHeading('Add a staff member')
                ->modalDescription('They get an email link to choose their own password. No password is set or sent from here.')
                ->modalSubmitActionLabel('Send invite')
                ->schema([
                    TextInput::make('name')->label('Full name')->required()->maxLength(255),
                    TextInput::make('email')->label('Email')->email()->required()->maxLength(255)
                        ->helperText('They sign in with this address. The link is valid for 48 hours.'),
                    Select::make('role')
                        ->label('Role')
                        ->options(fn (): array => app(StaffAccessService::class)->assignableRoles())
                        ->required()
                        ->native(false)
                        ->helperText('What they can see comes from the role. Edit roles on the Roles screen.'),
                ])
                ->action(function (array $data): void {
                    $user = app(StaffAccessService::class)->invite(
                        name: $data['name'],
                        email: $data['email'],
                        roleName: $data['role'],
                    );

                    if ($user instanceof User) {
                        // This email was already a Vendor contact's login —
                        // say so plainly, since the staff role just added
                        // sits alongside their existing vendor access rather
                        // than replacing it, and that would otherwise be
                        // invisible until someone noticed the "Vendor
                        // portal" tag on their row.
                        $body = $user->vendor
                            ? "{$data['email']} can now set a password and sign in. This email already had vendor portal access — that stays, alongside the new staff role."
                            : "{$data['email']} can now set a password and sign in.";

                        Notification::make()->title('Invite sent')->body($body)->success()->send();

                        return;
                    }

                    Notification::make()->title('Could not send the invite')->body('The account may exist — use "Resend invite" on the row to try again.')->danger()->send();
                }),
        ];
    }
}
