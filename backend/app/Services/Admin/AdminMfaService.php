<?php

namespace App\Services\Admin;

use App\DTOs\Admin\MfaEnrolmentData;
use App\Enums\Common\AuditAction;
use App\Enums\Settings\SettingKey;
use App\Exceptions\BusinessRuleViolation;
use App\Models\Admin;
use App\Repositories\Contracts\Admin\AdminRepositoryInterface;
use App\Services\Audit\AuditLogger;
use App\Services\Settings\SettingsService;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Contracts\Session\Session;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

/**
 * TOTP multi-factor authentication (ADR-06): enrolment with QR + confirmation code, one-time recovery codes,
 * replay protection (a time step is accepted once), enforcement per role (typed setting admin_mfa_required_roles).
 * The TOTP secret is encrypted at rest; recovery codes are stored only as keyed hashes.
 */
final class AdminMfaService
{
    private const SETUP_SESSION_KEY = 'admin.mfa.setup_secret';

    public function __construct(
        private readonly AdminRepositoryInterface $admins,
        private readonly AuditLogger $audit,
        private readonly AdminSessionService $sessions,
        private readonly DatabaseManager $db,
        private readonly Google2FA $totp,
        private readonly Session $session,
        private readonly SettingsService $settings,
    ) {}

    /**
     * Whether the admin holds a role that forces MFA.
     */
    public function isRequiredFor(Admin $admin): bool
    {
        /** @var list<string> $required */
        $required = array_map('strval', $this->settings->list(SettingKey::AdminMfaRequiredRoles));

        return $required !== [] && $admin->getRoleNames()->intersect($required)->isNotEmpty();
    }

    /**
     * Starts (or resumes) enrolment: a fresh secret kept in the session until the first valid code confirms it.
     */
    public function beginEnrolment(Admin $admin): MfaEnrolmentData
    {
        if ($admin->hasMfaEnabled()) {
            throw BusinessRuleViolation::make('mfa_already_enabled');
        }

        $secret = $this->session->get(self::SETUP_SESSION_KEY);

        if (! is_string($secret) || $secret === '') {
            $secret = $this->totp->generateSecretKey();
            $this->session->put(self::SETUP_SESSION_KEY, $secret);
        }

        $uri = $this->totp->getQRCodeUrl((string) config('admin.auth.mfa.issuer'), $admin->email, $secret);

        return new MfaEnrolmentData($secret, $uri, $this->qrSvg($uri));
    }

    /**
     * Confirms enrolment with the first code and returns the plain recovery codes (shown once).
     *
     * @return list<string>
     */
    public function confirmEnrolment(Admin $admin, string $code): array
    {
        $secret = $this->session->get(self::SETUP_SESSION_KEY);

        if (! is_string($secret) || $secret === '') {
            throw BusinessRuleViolation::make('mfa_setup_expired', 'code');
        }

        $step = $this->verifyTotp($secret, $code, null);

        if ($step === null) {
            throw BusinessRuleViolation::make('mfa_invalid_code', 'code');
        }

        $codes = $this->generateRecoveryCodes();

        $this->db->transaction(function () use ($admin, $secret, $step, $codes): void {
            $locked = $this->lock($admin);

            $locked->forceFill([
                'mfa_secret' => $secret,
                'mfa_enabled_at' => now(),
                'mfa_last_used_step' => $step,
                'mfa_recovery_codes' => $this->hashCodes($codes),
            ])->save();

            $this->audit->record(AuditAction::MfaEnabled, $locked, $locked);
        });

        $this->session->forget(self::SETUP_SESSION_KEY);

        return $codes;
    }

    /**
     * Turns MFA off for the admin themselves (current password required; refused when a role forces MFA).
     */
    public function disable(Admin $admin, string $password): void
    {
        $this->assertPassword($admin, $password);

        if ($this->isRequiredFor($admin)) {
            throw BusinessRuleViolation::make('mfa_required_for_role', 'password');
        }

        $this->db->transaction(function () use ($admin): void {
            $locked = $this->lock($admin);
            $locked->forceFill(['mfa_secret' => null, 'mfa_enabled_at' => null, 'mfa_recovery_codes' => null, 'mfa_last_used_step' => null])->save();

            $this->audit->record(AuditAction::MfaDisabled, $locked, $locked);
        });
    }

    /**
     * @return list<string> new plain recovery codes (the old ones stop working)
     */
    public function regenerateRecoveryCodes(Admin $admin, string $password): array
    {
        $this->assertPassword($admin, $password);

        if (! $admin->hasMfaEnabled()) {
            throw BusinessRuleViolation::make('mfa_not_enabled', 'password');
        }

        $codes = $this->generateRecoveryCodes();

        $this->db->transaction(function () use ($admin, $codes): void {
            $locked = $this->lock($admin);
            $locked->forceFill(['mfa_recovery_codes' => $this->hashCodes($codes)])->save();

            $this->audit->record(AuditAction::Updated, $locked, $locked, meta: ['mfa' => 'recovery_codes_regenerated']);
        });

        return $codes;
    }

    /**
     * Another admin resets this admin's MFA (lost device). Ends their sessions; they must enrol again.
     */
    public function reset(Admin $target, string $reason, Admin $actor): void
    {
        $this->db->transaction(function () use ($target, $reason, $actor): void {
            $locked = $this->lock($target);

            if (! $locked->hasMfaEnabled()) {
                throw BusinessRuleViolation::make('mfa_not_enabled');
            }

            $locked->forceFill(['mfa_secret' => null, 'mfa_enabled_at' => null, 'mfa_recovery_codes' => null, 'mfa_last_used_step' => null])->save();

            $this->audit->record(AuditAction::MfaReset, $locked, $actor, before: ['mfa_enabled' => true], after: ['mfa_enabled' => false], reason: $reason);
        });

        $this->sessions->revokeAll($target);
    }

    /**
     * Login second step: a TOTP code or a one-time recovery code.
     */
    public function verifyLoginCode(Admin $admin, string $code): bool
    {
        $code = trim($code);

        if (preg_match('/^\d{6}$/', $code) === 1) {
            return (bool) $this->db->transaction(function () use ($admin, $code): bool {
                $locked = $this->lock($admin);
                $step = $locked->mfa_secret === null ? null : $this->verifyTotp($locked->mfa_secret, $code, $locked->mfa_last_used_step);

                if ($step === null) {
                    return false;
                }

                $locked->forceFill(['mfa_last_used_step' => $step])->save();

                return true;
            });
        }

        return $this->consumeRecoveryCode($admin, $code);
    }

    public function recoveryCodesRemaining(Admin $admin): int
    {
        return count($admin->mfa_recovery_codes ?? []);
    }

    private function consumeRecoveryCode(Admin $admin, string $code): bool
    {
        $hash = $this->hashCode($code);

        return (bool) $this->db->transaction(function () use ($admin, $hash): bool {
            $locked = $this->lock($admin);
            $codes = $locked->mfa_recovery_codes ?? [];
            $index = array_search($hash, $codes, true);

            if ($index === false) {
                return false;
            }

            unset($codes[$index]);
            $locked->forceFill(['mfa_recovery_codes' => array_values($codes)])->save();

            $this->audit->record(AuditAction::Updated, $locked, $locked, meta: ['mfa' => 'recovery_code_used']);

            return true;
        });
    }

    private function verifyTotp(string $secret, string $code, ?int $lastStep): ?int
    {
        if (preg_match('/^\d{6}$/', trim($code)) !== 1) {
            return null;
        }

        $step = $this->totp->verifyKeyNewer($secret, trim($code), $lastStep ?? 0, (int) config('admin.auth.mfa.window', 1));

        return is_int($step) ? $step : null;
    }

    private function assertPassword(Admin $admin, string $password): void
    {
        if (! Hash::check($password, $admin->password)) {
            throw BusinessRuleViolation::make('password_incorrect', 'password');
        }
    }

    private function lock(Admin $admin): Admin
    {
        $locked = $this->admins->lockById($admin->getKey());

        return $locked instanceof Admin ? $locked : throw (new ModelNotFoundException)->setModel(Admin::class);
    }

    /**
     * @return list<string>
     */
    private function generateRecoveryCodes(): array
    {
        $codes = [];

        for ($i = 0; $i < (int) config('admin.auth.mfa.recovery_codes', 8); $i++) {
            $codes[] = Str::lower(Str::random(5).'-'.Str::random(5));
        }

        return $codes;
    }

    /**
     * @param list<string> $codes
     * @return list<string>
     */
    private function hashCodes(array $codes): array
    {
        return array_map($this->hashCode(...), $codes);
    }

    private function hashCode(string $code): string
    {
        return hash_hmac('sha256', Str::lower(trim($code)), (string) config('app.key'));
    }

    private function qrSvg(string $uri): string
    {
        $writer = new Writer(new ImageRenderer(new RendererStyle(220, 1), new SvgImageBackEnd));

        return $writer->writeString($uri);
    }
}
