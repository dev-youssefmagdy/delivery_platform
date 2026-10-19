<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\Admin\Auth\MfaChallengeRequest;
use App\Services\Admin\AdminAuthService;
use App\Support\Http\AdminFormResponse;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * Second login step: TOTP or recovery code. The admin is signed in only after this succeeds.
 */
final class MfaChallengeController extends AdminController
{
    public function __construct(private readonly AdminAuthService $auth) {}

    public function create(): View|RedirectResponse
    {
        return $this->auth->pendingAdmin() === null ? redirect()->route('admin.login') : view('admin.auth.mfa-challenge');
    }

    public function store(MfaChallengeRequest $request): AdminFormResponse
    {
        $this->auth->completeMfa($request->code());

        $intended = $request->session()->pull('url.intended');
        $url = is_string($intended) && str_starts_with($intended, url('/')) ? $intended : route('admin.home');

        return $this->formResponse(__('admin.auth.signed_in'))->redirect($url);
    }
}
