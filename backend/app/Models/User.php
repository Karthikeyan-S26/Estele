<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\PersonalAccessToken;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'phone', 'password', 'wallet_balance'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * Create a new API bearer token for this user (mobile app auth).
     */
    public function createApiToken(string $name = 'mobile', array $abilities = ['*'], ?\DateTimeInterface $expiresAt = null): string
    {
        return PersonalAccessToken::issue($this, $name, $abilities, $expiresAt);
    }

    /**
     * Revoke every bearer token this user holds (full logout everywhere).
     */
    public function revokeAllApiTokens(): void
    {
        PersonalAccessToken::where('user_id', $this->id)->delete();
    }

    /**
     * Revoke a single token by id — used by POST /api/logout.
     */
    public function revokeApiToken(int $tokenId): void
    {
        PersonalAccessToken::where('user_id', $this->id)->where('id', $tokenId)->delete();
    }

    public function apiTokens(): HasMany
    {
        return $this->hasMany(PersonalAccessToken::class);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        // Vendors hold a role but must NOT reach the Filament admin panel —
        // they interact through the tokenized web sell flow instead.
        return $this->hasAnyRole(['super_admin', 'marketing']);
    }

    public function orders(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Order::class)->latest();
    }

    public function addresses(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Address::class)->latest();
    }

    public function walletTransactions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(WalletTransaction::class)->latest();
    }

    public function rewardSubmissions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(RewardSubmission::class)->latest();
    }

    public function sellRequests(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SellRequest::class)->latest();
    }

    public function sellInvitations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SellInvitation::class, 'vendor_id')->latest();
    }

    public function oldJewelleryRequests(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(OldJewelleryRequest::class)->latest();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'wallet_balance' => 'decimal:2',
        ];
    }
}
