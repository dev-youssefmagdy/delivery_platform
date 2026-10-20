<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\RequestOtpRequest;
use App\Http\Requests\Api\V1\Auth\VerifyOtpRequest;
use App\Http\Resources\Api\V1\Identity\AuthSessionResource;
use App\Services\Identity\AuthService;
use App\Services\Identity\OtpService;
use App\Services\Identity\ProfileService;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * Phone OTP sign-in (SRS §20.1). The request answer is identical for every phone number.
 */
final class OtpController extends Controller
{
    public function __construct(
        private readonly OtpService $otp,
        private readonly AuthService $auth,
        private readonly ProfileService $profiles,
    ) {}

    public function request(RequestOtpRequest $request): JsonResponse
    {
        $issued = $this->otp->request($request->toDto());

        return ApiResponse::success(__('api.otp.sent'), [
            'expires_in' => $issued->expiresInSeconds,
            'resend_in' => $issued->resendInSeconds,
            'length' => $issued->length,
        ]);
    }

    public function verify(VerifyOtpRequest $request): JsonResponse
    {
        $session = $this->auth->verifyOtp($request->toDto());

        return ApiResponse::success(__('api.otp.verified'), new AuthSessionResource([
            'session' => $session,
            'me' => $this->profiles->me($session->user, $session->app),
        ]));
    }
}
