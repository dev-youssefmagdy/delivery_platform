<?php

namespace App\Services\Identity;

use App\DTOs\Identity\AuthSession;
use App\DTOs\Identity\VerifyOtpData;
use App\Enums\Identity\ClientApp;
use App\Enums\Identity\UserStatus;
use App\Exceptions\ForbiddenActionException;
use App\Models\User;
use App\Repositories\Contracts\Identity\AccessTokenRepositoryInterface;
use App\Repositories\Contracts\Identity\CustomerProfileRepositoryInterface;
use App\Repositories\Contracts\Identity\UserRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Turns a verified phone into a signed-in session: find or create the user, register the device and issue a
 * Sanctum token whose single ability is the app (`customer` or `provider`).
 */
final class AuthService
{
    public function __construct(
        private readonly OtpService $otp,
        private readonly UserRepositoryInterface $users,
        private readonly CustomerProfileRepositoryInterface $customerProfiles,
        private readonly AccessTokenRepositoryInterface $tokens,
        private readonly DeviceService $devices,
    ) {}

    /**
     * @throws \App\Exceptions\BusinessRuleViolation wrong / expired / exhausted code
     * @throws ForbiddenActionException suspended or deleted account (only after the code proved phone ownership)
     */
    public function verifyOtp(VerifyOtpData $data): AuthSession
    {
        $this->otp->verify($data->phone, $data->code, $data->purpose);

        return DB::transaction(function () use ($data): AuthSession {
            [$user, $created] = $this->users->firstOrCreateByPhone($data->phone, [
                'preferred_locale' => app()->getLocale(),
            ]);

            $this->assertCanSignIn($user);
            $this->users->markPhoneVerified($user);

            if ($data->app === ClientApp::Customer) {
                $this->customerProfiles->firstOrCreateFor($user);
            }

            $this->devices->register($user, $data->app, $data->device);

            return $this->issueSession($user, $data->app, $data->device->deviceId, $created);
        });
    }

    /**
     * Re-issues the token of the current device: the presented token is revoked, a fresh one is returned.
     */
    public function refresh(User $user, ClientApp $app, string $tokenName): AuthSession
    {
        return DB::transaction(function () use ($user, $app, $tokenName): AuthSession {
            $this->tokens->revokeNamed($user, $tokenName);

            return $this->issueSession($user, $app, $this->deviceIdFromTokenName($tokenName), false, $tokenName);
        });
    }

    public function logout(User $user): void
    {
        $name = $this->tokens->revokeCurrent($user);

        if ($name !== null && str_contains($name, ':')) {
            [$app, $deviceId] = explode(':', $name, 2);

            if (($client = ClientApp::tryFrom($app)) !== null) {
                $this->devices->forgetPushToken($user, $client, $deviceId);
            }
        }
    }

    public static function tokenName(ClientApp $app, string $deviceId): string
    {
        return $app->value.':'.Str::limit($deviceId, 100, '');
    }

    private function issueSession(User $user, ClientApp $app, string $deviceId, bool $created, ?string $name = null): AuthSession
    {
        $name ??= self::tokenName($app, $deviceId);
        $this->tokens->revokeNamed($user, $name);
        $expiresAt = now()->addDays((int) config('identity.token_ttl_days', 90));

        return new AuthSession(
            user: $user,
            token: $this->tokens->issue($user, $name, [$app->ability()], $expiresAt),
            expiresAt: $expiresAt,
            app: $app,
            isNewUser: $created,
        );
    }

    private function deviceIdFromTokenName(string $name): string
    {
        return str_contains($name, ':') ? explode(':', $name, 2)[1] : $name;
    }

    private function assertCanSignIn(User $user): void
    {
        if ($user->trashed() || $user->status === UserStatus::Deleted) {
            throw ForbiddenActionException::make('account_deleted');
        }

        if ($user->status === UserStatus::Suspended) {
            throw ForbiddenActionException::make('account_suspended');
        }
    }
}
