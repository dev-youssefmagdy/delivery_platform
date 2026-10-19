<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Enums\Admin\AdminLoginStep;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\Admin\Auth\LoginRequest;
use App\Services\Admin\AdminAuthService;
use App\Support\Http\AdminFormResponse;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Admin sign-in (public routes). Answers the AJAX form with redirect instructions.
 */
final class LoginController extends AdminController
{
    public function __construct(private readonly AdminAuthService $auth) {}

    public function create(): View
    {
        // Coming back to the login page abandons a half-finished MFA step.
        $this->auth->cancelPending();

        return view('admin.auth.login');
    }

    public function store(LoginRequest $request): AdminFormResponse
    {
        $step = $this->auth->attempt($request->toDto());

        return match ($step) {
            AdminLoginStep::MfaChallenge => $this->formResponse()->redirect(route('admin.mfa.challenge')),
            AdminLoginStep::Authenticated => $this->formResponse(__('admin.auth.signed_in'))->redirect($this->intendedUrl($request)),
        };
    }

    private function intendedUrl(Request $request): string
    {
        $intended = $request->session()->pull('url.intended');

        return is_string($intended) && str_starts_with($intended, url('/')) ? $intended : route('admin.home');
    }
}
