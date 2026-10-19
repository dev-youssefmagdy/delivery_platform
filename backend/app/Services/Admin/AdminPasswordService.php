<?php

namespace App\Services\Admin;

use App\DTOs\Admin\PasswordResetData;
use App\Enums\Common\AuditAction;
use App\Exceptions\BusinessRuleViolation;
use App\Models\Admin;
use App\Services\Audit\AuditLogger;
use Illuminate\Contracts\Auth\PasswordBroker;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/**
 * Forgot / reset / change password for admins. A reset or change ends the other sessions of the admin.
 */
final class AdminPasswordService
{
    public function __construct(
        private readonly AdminSessionService $sessions,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * Sends the reset link when the email belongs to an admin. The caller always shows the same message, so
     * accounts cannot be enumerated.
     */
    public function sendResetLink(string $email): void
    {
        $this->broker()->sendResetLink(['email' => mb_strtolower(trim($email))]);
    }

    public function reset(PasswordResetData $data): void
    {
        $status = $this->broker()->reset(
            ['email' => mb_strtolower(trim($data->email)), 'token' => $data->token, 'password' => $data->password],
            function (Admin $admin, string $password): void {
                $admin->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();

                $this->sessions->revokeAll($admin);
                $this->audit->record(AuditAction::PasswordChanged, $admin, $admin, meta: ['via' => 'reset']);
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw BusinessRuleViolation::make('password_reset_invalid', 'email');
        }
    }

    /**
     * Self-service change; the other sessions end, the current one stays.
     */
    public function change(Admin $admin, string $currentPassword, string $newPassword, string $currentSessionId): void
    {
        if (! Hash::check($currentPassword, $admin->password)) {
            throw BusinessRuleViolation::make('password_incorrect', 'current_password');
        }

        $admin->forceFill(['password' => $newPassword, 'remember_token' => Str::random(60)])->save();

        $this->sessions->revokeAll($admin, $currentSessionId);
        $this->audit->record(AuditAction::PasswordChanged, $admin, $admin, meta: ['via' => 'profile']);
    }

    /**
     * Creates the invitation token for a newly created admin (the link opens the reset-password form).
     */
    public function createInvitationToken(Admin $admin): string
    {
        return $this->broker()->createToken($admin);
    }

    private function broker(): PasswordBroker
    {
        return Password::broker('admins');
    }
}
