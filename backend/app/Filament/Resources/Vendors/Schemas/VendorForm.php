<?php

namespace App\Filament\Resources\Vendors\Schemas;

use App\Models\User;
use App\Services\Vendors\PanelAccessService;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class VendorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Vendor')
                ->columns(2)
                ->schema([
                    TextInput::make('name')->required()->maxLength(255),
                    TextInput::make('company_name')->label('Company')->maxLength(255),
                    TextInput::make('mobile')
                        ->required()
                        ->tel()
                        ->unique(ignoreRecord: true)
                        ->helperText(fn ($record) => $record?->mobile_verified_at
                            ? "Verified on {$record->mobile_verified_at->format('d M Y')}"
                            : 'Not verified yet — use "Verify mobile" after saving.'),
                    TextInput::make('whatsapp_number')
                        ->label('WhatsApp')
                        ->tel()
                        ->helperText('Bid invites and links also go here. Leave blank to use the mobile number.'),
                    Toggle::make('is_active')->label('Active — receives bid invitations')->default(true)->columnSpanFull(),
                ]),

            Section::make('Panel login')
                ->description('Optional. With an email the vendor gets a link to set a password and can see their requests in this panel.')
                ->columns(2)
                ->schema([
                    Placeholder::make('panel_access_scope')
                        ->label('What they can see')
                        ->columnSpanFull()
                        // Fixed and the same for every vendor — there is no
                        // per-vendor permission to configure, so this is the
                        // one place an admin needs to check that, instead of
                        // Shield's Roles screen (which covers every resource
                        // in the store, not just vendors). If that ever
                        // needs to change, it changes for every vendor at
                        // once via the 'vendor' role in ShieldSeeder.
                        ->content('Only the old-jewellery requests they were personally invited to bid on — nothing else in the store, and not even other vendors\' bids on the same request. Every vendor contact gets exactly this, and it never needs configuring per vendor.'),
                    TextInput::make('email')
                        ->email()
                        ->unique(ignoreRecord: true)
                        ->maxLength(255)
                        // A vendor invite grants a real panel login for this
                        // address — if that email already belongs to someone
                        // else's account (a customer, staff, another vendor's
                        // login), the setup link would hand that account's
                        // password to whoever receives this invite instead.
                        ->rule(function ($record) {
                            return function (string $attribute, $value, \Closure $fail) use ($record) {
                                $ownedByThisVendor = $record?->user_id;

                                $existing = User::where('email', $value)->first();

                                if ($existing && $existing->id !== $ownedByThisVendor) {
                                    $fail(PanelAccessService::collisionMessage($existing));
                                }
                            };
                        }),
                    Placeholder::make('panel_login')
                        ->label('Status')
                        ->content(fn ($record) => match (true) {
                            $record === null => 'Link is sent when you save with an email.',
                            blank($record->email) => 'No email — no login.',
                            $record->user === null => 'Not invited yet — save to send the link.',
                            filled($record->user->password) => 'Active — password set.',
                            default => 'Invited — waiting for password.',
                        }),
                ]),
        ]);
    }
}
