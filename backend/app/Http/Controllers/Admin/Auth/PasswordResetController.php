<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\Admin\Auth\ForgotPasswordRequest;
use App\Http\Requests\Admin\Auth\ResetPasswordRequest;
use App\Services\Admin\AdminPasswordService;
use App\Support\Http\AdminFormResponse;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Forgot / reset password (also opens admin invitations). The "link sent" answer never reveals whether the
 * email belongs to an account.
 */
final class PasswordResetController extends AdminController
{
    public function __construct(private readonly AdminPasswordService $passwords) {}

    public function request(): View
    {
        return view('admin.auth.forgot-password');
    }

    public function email(ForgotPasswordRequest $request): AdminFormResponse
    {
        $this->passwords->sendResetLink((string) $request->validated('email'));

        return $this->formResponse(__('admin.auth.reset_link_sent'))->reset();
    }

    public function reset(Request $request, string $token): View
    {
        return view('admin.auth.reset-password', ['token' => $token, 'email' => (string) $request->query('email', '')]);
    }

    public function update(ResetPasswordRequest $request): AdminFormResponse
    {
        $this->passwords->reset($request->toDto());

        return $this->formResponse(__('admin.auth.password_updated'))->redirect(route('admin.login'));
    }
}
