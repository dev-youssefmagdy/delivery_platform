<?php

namespace App\Http\Controllers\Admin\Security;

use App\DTOs\Admin\ProfileData;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\Admin\Security\ChangePasswordRequest;
use App\Http\Requests\Admin\Security\UpdateProfileRequest;
use App\Services\Admin\AdminMfaService;
use App\Services\Admin\AdminPasswordService;
use App\Services\Admin\AdminProfileService;
use App\Services\Admin\AdminSessionService;
use App\Support\Http\AdminFormResponse;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * "My account": profile, password, MFA status and active sessions of the signed-in admin (self-service).
 */
final class SecurityController extends AdminController
{
    public function __construct(
        private readonly AdminProfileService $profile,
        private readonly AdminPasswordService $passwords,
        private readonly AdminSessionService $sessions,
        private readonly AdminMfaService $mfa,
    ) {}

    public function index(Request $request): View
    {
        $admin = $this->actor();

        return view('admin.security.index', [
            'admin' => $admin->loadMissing('roles'),
            'sessions' => $this->sessions->listFor($admin),
            'currentSessionId' => $request->session()->getId(),
            'mfaRequired' => $this->mfa->isRequiredFor($admin),
            'recoveryCodesRemaining' => $this->mfa->recoveryCodesRemaining($admin),
        ]);
    }

    public function updateProfile(UpdateProfileRequest $request): AdminFormResponse
    {
        $this->profile->update($this->actor(), ProfileData::fromRequest($request));

        return $this->formResponse(__('admin.security.profile_updated'))->reload();
    }

    public function changePassword(ChangePasswordRequest $request): AdminFormResponse
    {
        $this->passwords->change(
            $this->actor(),
            (string) $request->validated('current_password'),
            (string) $request->validated('password'),
            $request->session()->getId(),
        );

        return $this->formResponse(__('admin.security.password_changed'))->reset()->reload();
    }

    public function revokeSession(Request $request, int $session): AdminFormResponse
    {
        $this->sessions->revoke($this->actor(), $session, $request->session()->getId());

        return $this->formResponse(__('admin.security.session_revoked'))->reload();
    }

    public function revokeOtherSessions(Request $request): AdminFormResponse
    {
        $count = $this->sessions->revokeAll($this->actor(), $request->session()->getId());

        return $this->formResponse(trans_choice('admin.security.sessions_revoked', $count, ['count' => $count]))->reload();
    }
}
