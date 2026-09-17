<?php

namespace App\Filament\Resources\Staff\Tables;

use App\Filament\Resources\Staff\StaffResource;
use App\Filament\Resources\Vendors\VendorResource;
use App\Models\User;
use App\Models\Vendor;
use App\Services\Staff\StaffAccessService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\PaginationMode;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class StaffTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->paginationMode(PaginationMode::Simple)
            ->disabledSelection()
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')->searchable()->description(fn (User $record) => $record->email),
                TextColumn::make('roles.name')
                    ->label('Role')
                    ->badge()
                    // Vendor/Admin (Vendor::ACCESS_ROLES) are shown in their
                    // own "Vendor portal" column instead — mixing them in
                    // here made a dual-role person's staff role hard to spot
                    // next to a role that isn't really "their job", it's a
                    // side effect of also being a vendor contact.
                    ->state(fn (User $record) => $record->roles->pluck('name')->diff(Vendor::ACCESS_ROLES)->all())
                    ->formatStateUsing(fn (string $state) => Str::headline($state))
                    ->color(fn (string $state) => $state === 'super_admin' ? 'warning' : 'info')
                    ->placeholder('—'),
                TextColumn::make('vendor.id')
                    ->label('Vendor portal')
                    ->state(fn (User $record) => $record->vendor ? 'Also a vendor contact' : null)
                    ->badge()
                    ->color('gray')
                    ->placeholder('—')
                    ->url(fn (User $record) => $record->vendor ? VendorResource::getUrl('view', ['record' => $record->vendor]) : null)
                    ->tooltip('This login also has bidding access, managed on the Vendors screen.'),
                TextColumn::make('status')
                    ->badge()
                    ->state(fn (User $record) => filled($record->password) ? 'Active' : 'Invite pending')
                    ->color(fn (string $state) => $state === 'Active' ? 'success' : 'gray'),
                TextColumn::make('created_at')->label('Added')->date('d M Y')->sortable(),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->relationship('roles', 'name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => Str::headline($record->name)),
            ])
            ->recordActions([
                self::changeRoleAction(),
                ActionGroup::make([
                    self::resendInviteAction(),
                    self::removeAccessAction(),
                ]),
            ]);
    }

    private static function changeRoleAction(): Action
    {
        return Action::make('change_role')
            ->label('Change role')
            ->icon(Heroicon::OutlinedArrowsRightLeft)
            ->visible(fn (User $record) => StaffResource::canEdit($record) && ! $record->hasRole('super_admin'))
            ->fillForm(fn (User $record) => ['role' => $record->roles->pluck('name')->diff(Vendor::ACCESS_ROLES)->first()])
            ->schema([
                Select::make('role')
                    ->label('Role')
                    ->options(fn (): array => app(StaffAccessService::class)->assignableRoles())
                    ->required()
                    ->native(false),
            ])
            ->action(function (array $data, User $record): void {
                // Only ever swap the staff role. A vendor/admin role from the
                // Vendors screen must survive this unchanged — syncRoles()
                // replaces the whole list, so it has to be re-added
                // explicitly rather than assumed to stay.
                $keepManaged = $record->roles->pluck('name')->intersect(Vendor::ACCESS_ROLES);
                $record->syncRoles($keepManaged->push($data['role'])->unique()->all());

                Notification::make()->title("{$record->name} is now ".Str::headline($data['role']))->success()->send();
            });
    }

    private static function resendInviteAction(): Action
    {
        return Action::make('resend_invite')
            ->label('Resend invite')
            ->icon(Heroicon::OutlinedEnvelope)
            ->visible(fn (User $record) => StaffResource::canEdit($record) && blank($record->password))
            ->requiresConfirmation()
            ->modalDescription(fn (User $record) => "A fresh password link goes to {$record->email}. The old link stops working.")
            ->action(function (User $record): void {
                $sent = app(StaffAccessService::class)->sendSetupLink($record, $record->roles->pluck('name')->first() ?? 'staff', isResend: true);

                $sent
                    ? Notification::make()->title("Invite resent to {$record->email}")->success()->send()
                    : Notification::make()->title('Could not send the invite.')->danger()->send();
            });
    }

    private static function removeAccessAction(): Action
    {
        return Action::make('remove_access')
            ->label('Remove access')
            ->icon(Heroicon::OutlinedNoSymbol)
            ->color('danger')
            ->visible(fn (User $record) => StaffResource::canDelete($record) && $record->id !== auth()->id() && ! $record->hasRole('super_admin'))
            ->requiresConfirmation()
            ->modalHeading(fn (User $record) => "Remove {$record->name}'s panel access?")
            ->modalDescription(fn (User $record) => $record->vendor
                ? 'They lose their staff role. Their vendor bidding login stays — remove that separately on the Vendors screen if needed.'
                : 'They lose every role and their password, so they can no longer sign in. Their history stays.')
            ->action(function (User $record): void {
                $service = app(StaffAccessService::class);

                // Vendor/admin is the Vendors screen's role to revoke, never
                // this button's — otherwise "remove access" here could
                // quietly also kill someone's separately-managed vendor
                // login, which nothing on this screen would explain.
                foreach ($record->roles->pluck('name')->diff(Vendor::ACCESS_ROLES) as $role) {
                    $service->revokeRole($record, $role);
                }

                Notification::make()->title('Access removed')->success()->send();
            });
    }
}
