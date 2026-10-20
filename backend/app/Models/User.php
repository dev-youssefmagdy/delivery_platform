<?php

namespace App\Models;

use App\Enums\Identity\UserStatus;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

/**
 * A mobile account (customer and/or provider). The verified phone is the identity; there is no password
 * (ADR-60). Mass assignment is deliberately narrow: `status`, `phone_e164` and verification timestamps are only
 * set by services, never from request input.
 *
 * @property int $id
 * @property string $uuid
 * @property string|null $name
 * @property string|null $phone_e164
 * @property Carbon|null $phone_verified_at
 * @property string|null $email
 * @property string|null $photo_path
 * @property string|null $preferred_locale
 * @property UserStatus $status
 * @property string|null $status_reason why the account is suspended/restricted (shown to admins only)
 * @property Carbon|null $status_changed_at
 * @property Carbon|null $status_expires_at when a temporary suspension ends (auto-reactivation job)
 * @property Carbon|null $last_seen_at
 * @property Carbon|null $anonymised_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 * @property-read CustomerProfile|null $customerProfile
 * @property-read ProviderProfile|null $providerProfile
 */
class User extends Authenticatable implements HasLocalePreference
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = ['name', 'email', 'preferred_locale', 'photo_path'];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'active',
    ];

    protected static function booted(): void
    {
        static::creating(static function (self $user): void {
            $user->uuid ??= (string) Str::uuid();
        });
    }

    /**
     * @return HasOne<CustomerProfile, $this>
     */
    public function customerProfile(): HasOne
    {
        return $this->hasOne(CustomerProfile::class);
    }

    /**
     * @return HasOne<ProviderProfile, $this>
     */
    public function providerProfile(): HasOne
    {
        return $this->hasOne(ProviderProfile::class);
    }

    /**
     * @return HasMany<UserDevice, $this>
     */
    public function devices(): HasMany
    {
        return $this->hasMany(UserDevice::class);
    }

    /**
     * @return HasMany<UserConsent, $this>
     */
    public function consents(): HasMany
    {
        return $this->hasMany(UserConsent::class);
    }

    /**
     * @return HasMany<AccountDeletionRequest, $this>
     */
    public function deletionRequests(): HasMany
    {
        return $this->hasMany(AccountDeletionRequest::class);
    }

    /**
     * @return HasMany<CustomerMessage, $this>
     */
    public function customerMessages(): HasMany
    {
        return $this->hasMany(CustomerMessage::class);
    }

    public function isAnonymised(): bool
    {
        return $this->anonymised_at !== null;
    }

    public function preferredLocale(): ?string
    {
        return $this->preferred_locale;
    }

    public function isProfileComplete(): bool
    {
        return $this->name !== null && trim($this->name) !== '';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => UserStatus::class,
            'phone_verified_at' => 'datetime',
            'status_changed_at' => 'datetime',
            'status_expires_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'anonymised_at' => 'datetime',
        ];
    }
}
