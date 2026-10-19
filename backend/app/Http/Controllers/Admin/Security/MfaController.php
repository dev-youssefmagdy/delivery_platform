<?php

namespace App\Http\Controllers\Admin\Security;

use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\Admin\Security\ConfirmMfaRequest;
use App\Http\Requests\Admin\Security\PasswordConfirmationRequest;
use App\Services\Admin\AdminMfaService;
use App\Support\Http\AdminFormResponse;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * TOTP enrolment (QR + confirmation code), one-time recovery codes, disable.
 */
final class MfaController extends AdminController
{
    private const RECOVERY_FLASH = 'admin.mfa.recovery_codes';

    public function __construct(private readonly AdminMfaService $mfa) {}

    public function show(): View|RedirectResponse
    {
        $admin = $this->actor();

        if ($admin->hasMfaEnabled()) {
            return redirect()->route('admin.security.index');
        }

        return view('admin.security.mfa', ['enrolment' => $this->mfa->beginEnrolment($admin)]);
    }

    public function confirm(ConfirmMfaRequest $request): AdminFormResponse
    {
        $codes = $this->mfa->confirmEnrolment($this->actor(), (string) $request->validated('code'));
        $request->session()->flash(self::RECOVERY_FLASH, $codes);

        return $this->formResponse(__('admin.security.mfa_enabled'))->redirect(route('admin.security.mfa.recovery'));
    }

    public function recoveryCodes(Request $request): View|RedirectResponse
    {
        $codes = $request->session()->get(self::RECOVERY_FLASH);

        if (! is_array($codes) || $codes === []) {
            return redirect()->route('admin.security.index');
        }

        return view('admin.security.recovery-codes', ['codes' => $codes]);
    }

    public function regenerate(PasswordConfirmationRequest $request): AdminFormResponse
    {
        $codes = $this->mfa->regenerateRecoveryCodes($this->actor(), (string) $request->validated('password'));
        $request->session()->flash(self::RECOVERY_FLASH, $codes);

        return $this->formResponse(__('admin.security.recovery_regenerated'))->redirect(route('admin.security.mfa.recovery'));
    }

    public function disable(PasswordConfirmationRequest $request): AdminFormResponse
    {
        $this->mfa->disable($this->actor(), (string) $request->validated('password'));

        return $this->formResponse(__('admin.security.mfa_disabled'))->redirect(route('admin.security.index'));
    }
}
