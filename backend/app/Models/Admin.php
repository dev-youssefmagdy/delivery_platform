<?php

namespace App\Models;

use App\Enums\Admin\AdminRole;
use App\Enums\Admin\AdminStatus;
use App\Notifications\Admin\AdminPasswordNotification;
use App\Support\Audit\Auditable;
use Database\Factories\AdminFactory;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\RoutesNotifications;
use Illuminate\Support\Carbon;
use Spatie\Permission\Traits\HasRoles;

/**
 * A back-office operator (ADR-06): separate table + `admin` session guard. Permissions come from Spatie roles
 * (guard `admin`); Super Admin passes every check through Gate::before.
 *
 * @property int $id
 * @property string $uuid
 * @property string $name
 * @property string $email
 * @property string|null $phone
 * @property string $password
 * @property string|null $locale
 * @property AdminStatus $status
 * @property string|null $mfa_secret encrypted at rest
 * @property list<string>|null $mfa_recovery_codes HMAC hashes of the unused recovery codes, encrypted at rest
 * @property int|null $mfa_last_used_step last accepted TOTP time step (replay protection)
 * @property Carbon|null $mfa_enabled_at
 * @property Carbon|null $suspended_at
 * @property Carbon|null $last_login_at
 * @property int $lock_version
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Role> $roles
 */
class Admin extends Authenticatable implements CanResetPasswordContract, HasLocalePreference
{
    use Auditable;
    use CanResetPassword;

    /** @use HasFactory<AdminFactory> */
    use HasFactory;

    use HasRoles;
    use RoutesNotifications;
    use SoftDeletes;

    protected string $guard_name = 'admin';

    /** @var list<string> */
    protected array $auditExcluded = ['uuid', 'mfa_last_used_step', 'last_login_at', 'deleted_at', 'updated_at', 'created_at'];

    protected $fillable = [
        'name', 'email', 'phone', 'password', 'locale', 'status', 'mfa_secret', 'mfa_recovery_codes', 'mfa_last_used_step',
        'mfa_enabled_at', 'suspended_at', 'last_login_at', 'lock_version',
    ];

    protected $hidden = ['password', 'remember_token', 'mfa_secret', 'mfa_recovery_codes'];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'phone' => null,
        'locale' => null,
        'status' => 'active',
        'mfa_secret' => null,
        'mfa_recovery_codes' => null,
        'mfa_last_used_step' => null,
        'mfa_enabled_at' => null,
        'suspended_at' => null,
        'last_login_at' => null,
        'remember_token' => null,
        'lock_version' => 0,
    ];

    protected static function booted(): void
    {
        static::creating(static function (Admin $admin): void {
            $admin->uuid ??= (string) \Illuminate\Support\Str::uuid();
        });
    }

    public function preferredLocale(): ?string
    {
        return $this->locale;
    }

    public function isActive(): bool
    {
        return $this->status === AdminStatus::Active;
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(AdminRole::SuperAdmin->value);
    }

    public function hasMfaEnabled(): bool
    {
        return $this->mfa_enabled_at !== null && $this->mfa_secret !== null;
    }

    /**
     * @return HasMany<AdminSession, $this>
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(AdminSession::class);
    }

    /**
     * @return HasMany<AdminLoginEvent, $this>
     */
    public function loginEvents(): HasMany
    {
        return $this->hasMany(AdminLoginEvent::class);
    }

    /**
     * Audit snapshot: no secrets (hidden), plus the assigned role names.
     *
     * @return array<string, mixed>
     */
    public function auditSnapshot(): array
    {
        $snapshot = array_diff_key($this->attributesToArray(), array_flip(array_merge($this->getHidden(), $this->auditExcludedAttributes())));
        $snapshot['mfa_enabled'] = $this->mfa_enabled_at !== null;
        $snapshot['roles'] = $this->getRoleNames()->sort()->values()->all();

        return $snapshot;
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new AdminPasswordNotification((string) $token));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => AdminStatus::class,
            'password' => 'hashed',
            'mfa_secret' => 'encrypted',
            'mfa_recovery_codes' => 'encrypted:array',
            'mfa_last_used_step' => 'integer',
            'mfa_enabled_at' => 'datetime',
            'suspended_at' => 'datetime',
            'last_login_at' => 'datetime',
            'lock_version' => 'integer',
        ];
    }
}
