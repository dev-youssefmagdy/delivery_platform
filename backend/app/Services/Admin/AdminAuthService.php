<?php

namespace App\Services\Admin;

use App\DTOs\Admin\LoginData;
use App\Enums\Admin\AdminLoginOutcome;
use App\Enums\Admin\AdminLoginStep;
use App\Enums\Common\AuditAction;
use App\Enums\Settings\SettingKey;
use App\Exceptions\BusinessRuleViolation;
use App\Models\Admin;
use App\Repositories\Contracts\Admin\AdminLoginEventRepositoryInterface;
use App\Repositories\Contracts\Admin\AdminRepositoryInterface;
use App\Services\Audit\AuditLogger;
use App\Services\Settings\SettingsService;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Session\Session;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Admin sign-in (ADR-06): throttled credential check with a generic error, optional TOTP second step (the
 * admin is signed in only after it), login activity + audit entries, tracked sessions.
 */
final class AdminAuthService
{
    public const PENDING_KEY = 'admin.mfa.pending';

    public function __construct(
        private readonly AdminRepositoryInterface $admins,
        private readonly AdminLoginEventRepositoryInterface $events,
        private readonly AdminSessionService $sessions,
        private readonly AdminMfaService $mfa,
        private readonly AuditLogger $audit,
        private readonly AuthFactory $auth,
        private readonly Session $session,
        private readonly SettingsService $settings,
    ) {}

    public function attempt(LoginData $data): AdminLoginStep
    {
        $email = mb_strtolower(trim($data->email));
        $throttleKey = $this->throttleKey($email);
        $maxAttempts = $this->settings->int(SettingKey::RateLimitAdminLoginPerMinute);

        if (RateLimiter::tooManyAttempts($throttleKey, $maxAttempts)) {
            $this->record(AdminLoginOutcome::Throttled, $email);

            throw new ThrottleRequestsException('', null, ['Retry-After' => (string) RateLimiter::availableIn($throttleKey)]);
        }

        $admin = $this->admins->findByEmail($email);

        // Always run one hash check, even for unknown emails, so response times do not reveal accounts.
        $passwordOk = Hash::check($data->password, $admin instanceof Admin ? $admin->password : Hash::make(Str::random(32)));

        if (! $admin instanceof Admin || ! $passwordOk || ! $admin->isActive()) {
            RateLimiter::hit($throttleKey, $this->settings->int(SettingKey::AdminLoginLockoutSeconds));
            $this->record($admin instanceof Admin && $passwordOk ? AdminLoginOutcome::AccountSuspended : AdminLoginOutcome::InvalidCredentials, $email, $admin);

            // One generic message for unknown email, wrong password and suspended accounts.
            throw BusinessRuleViolation::make('invalid_credentials', 'email');
        }

        RateLimiter::clear($throttleKey);

        if ($admin->hasMfaEnabled()) {
            $this->session->put(self::PENDING_KEY, [
                'admin_id' => $admin->getKey(),
                'remember' => $data->remember,
                'expires_at' => now()->addSeconds((int) config('admin.auth.mfa.pending_ttl_seconds', 300))->getTimestamp(),
                'attempts' => 0,
            ]);

            return AdminLoginStep::MfaChallenge;
        }

        $this->completeLogin($admin, $data->remember);

        return AdminLoginStep::Authenticated;
    }

    /**
     * The admin waiting for the second step, or null when there is none (or it expired).
     */
    public function pendingAdmin(): ?Admin
    {
        $pending = $this->pending();

        if ($pending === null) {
            return null;
        }

        $admin = $this->admins->find($pending['admin_id']);

        return $admin instanceof Admin && $admin->isActive() && $admin->hasMfaEnabled() ? $admin : null;
    }

    /**
     * Second step: TOTP or recovery code. Five wrong codes end the pending login.
     */
    public function completeMfa(string $code): void
    {
        $pending = $this->pending();
        $admin = $this->pendingAdmin();

        if ($pending === null || $admin === null) {
            $this->session->forget(self::PENDING_KEY);

            throw BusinessRuleViolation::make('mfa_session_expired', 'code');
        }

        if (! $this->mfa->verifyLoginCode($admin, $code)) {
            $attempts = $pending['attempts'] + 1;
            $this->record(AdminLoginOutcome::MfaFailed, $admin->email, $admin);

            if ($attempts >= (int) config('admin.auth.mfa.max_attempts', 5)) {
                $this->session->forget(self::PENDING_KEY);
                RateLimiter::hit($this->throttleKey(mb_strtolower($admin->email)), $this->settings->int(SettingKey::AdminLoginLockoutSeconds));

                throw BusinessRuleViolation::make('mfa_session_expired', 'code');
            }

            $this->session->put(self::PENDING_KEY, ['attempts' => $attempts] + $pending);

            throw BusinessRuleViolation::make('mfa_invalid_code', 'code');
        }

        $this->session->forget(self::PENDING_KEY);
        $this->completeLogin($admin, $pending['remember']);
    }

    public function cancelPending(): void
    {
        $this->session->forget(self::PENDING_KEY);
    }

    public function logout(Admin $admin): void
    {
        $guard = $this->auth->guard('admin');
        $sessionId = $this->session->getId();

        $this->record(AdminLoginOutcome::Logout, $admin->email, $admin);
        $this->audit->record(AuditAction::LoggedOut, $admin, $admin);
        $this->sessions->end($sessionId);

        $guard->logout();
        $this->session->invalidate();
        $this->session->regenerateToken();
    }

    private function completeLogin(Admin $admin, bool $remember): void
    {
        $this->auth->guard('admin')->login($admin, $remember);

        $this->session->put(AdminSessionService::MARKER, true);
        $this->sessions->start($admin, $this->session->getId());

        $admin->forceFill(['last_login_at' => now()])->saveQuietly();

        $this->record(AdminLoginOutcome::Success, $admin->email, $admin);
        $this->audit->record(AuditAction::LoggedIn, $admin, $admin);
    }

    /**
     * @return array{admin_id: int, remember: bool, expires_at: int, attempts: int}|null
     */
    private function pending(): ?array
    {
        $pending = $this->session->get(self::PENDING_KEY);

        if (! is_array($pending) || ! isset($pending['admin_id'], $pending['expires_at']) || $pending['expires_at'] < now()->getTimestamp()) {
            return null;
        }

        return [
            'admin_id' => (int) $pending['admin_id'],
            'remember' => (bool) ($pending['remember'] ?? false),
            'expires_at' => (int) $pending['expires_at'],
            'attempts' => (int) ($pending['attempts'] ?? 0),
        ];
    }

    private function record(AdminLoginOutcome $outcome, ?string $email, ?Admin $admin = null): void
    {
        $ip = Context::getHidden('ip');
        $userAgent = Context::getHidden('user_agent');

        $this->events->append([
            'admin_id' => $admin?->getKey(),
            'email' => $email === null ? null : Str::limit($email, 191, ''),
            'outcome' => $outcome,
            'ip' => is_string($ip) ? $ip : null,
            'user_agent' => is_string($userAgent) ? Str::limit($userAgent, 512, '') : null,
        ]);

        if ($outcome->isFailure()) {
            $this->audit->record(AuditAction::LoginFailed, 'admin_login', null, meta: ['outcome' => $outcome->value, 'email' => $email]);
        }
    }

    private function throttleKey(string $email): string
    {
        $ip = Context::getHidden('ip');

        return 'admin-login:'.$email.'|'.(is_string($ip) ? $ip : '');
    }
}
