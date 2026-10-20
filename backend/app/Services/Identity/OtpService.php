<?php

namespace App\Services\Identity;

use App\DTOs\Identity\OtpIssued;
use App\DTOs\Identity\RequestOtpData;
use App\Enums\Identity\OtpDeliveryStatus;
use App\Enums\Identity\OtpPurpose;
use App\Enums\Settings\SettingKey;
use App\Exceptions\BusinessRuleViolation;
use App\Exceptions\RateLimitedException;
use App\Exceptions\ServiceUnavailableException;
use App\Integrations\Sms\Contracts\SmsGateway;
use App\Integrations\Sms\Exceptions\SmsDeliveryException;
use App\Models\OtpChallenge;
use App\Repositories\Contracts\Identity\OtpChallengeRepositoryInterface;
use App\Services\Settings\SettingsService;
use App\Support\Auth\PhoneNumber;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Phone OTP: generation (crypto-random, stored only as an HMAC), cooldown, expiry and attempt limits (SRS §6.2,
 * §20.7). Every limit is a typed setting (`otp_*`). Responses never depend on whether the phone has an account.
 */
final class OtpService
{
    public function __construct(
        private readonly OtpChallengeRepositoryInterface $challenges,
        private readonly SettingsService $settings,
        private readonly Container $container,
    ) {}

    /**
     * @throws RateLimitedException when the resend cooldown of the previous code is still running
     * @throws ServiceUnavailableException when the SMS could not be sent
     */
    public function request(RequestOtpData $data): OtpIssued
    {
        $phoneHash = PhoneNumber::hash($data->phone);
        $length = $this->settings->int(SettingKey::OtpLength);
        $ttl = $this->settings->int(SettingKey::OtpTtlSeconds);
        $cooldown = $this->settings->int(SettingKey::OtpResendCooldownSeconds);

        // Serialises concurrent requests of one phone so the cooldown cannot be raced.
        $lock = Cache::lock('otp:request:'.$phoneHash, 15);
        $lock->block(5);

        try {
            [$challenge, $code] = $this->issue($data, $phoneHash, $length, $ttl, $cooldown);
            $this->deliver($challenge, $data->phone, $code);
        } finally {
            $lock->release();
        }

        return new OtpIssued($ttl, $cooldown, $length);
    }

    /**
     * Checks a code. Attempts are counted even though the call then throws, so the counter commits first.
     *
     * @throws BusinessRuleViolation `otp_invalid`, `otp_expired`, `otp_attempts_exceeded`
     */
    public function verify(string $phone, string $code, OtpPurpose $purpose): void
    {
        $phoneHash = PhoneNumber::hash($phone);
        $code = $this->normalizeCode($code);

        /** @var array{0: string, 1: int} $outcome */
        $outcome = DB::transaction(function () use ($phoneHash, $purpose, $code): array {
            $challenge = $this->challenges->lockLatestFor($phoneHash, $purpose);

            if ($challenge === null || $challenge->consumed_at !== null || $challenge->invalidated_at !== null) {
                return ['invalid', 0];
            }

            if ($challenge->attempts >= $challenge->max_attempts) {
                return ['locked', 0];
            }

            if ($challenge->expires_at->lte(now())) {
                return ['expired', 0];
            }

            if (hash_equals($challenge->code_hash, $this->codeHash($phoneHash, $purpose, $code))) {
                $this->challenges->markConsumed($challenge);

                return ['ok', 0];
            }

            $this->challenges->incrementAttempts($challenge);
            $remaining = max(0, $challenge->max_attempts - $challenge->attempts);

            return [$remaining === 0 ? 'locked' : 'invalid', $remaining];
        });

        match ($outcome[0]) {
            'ok' => null,
            'expired' => throw BusinessRuleViolation::make('otp_expired', 'code'),
            'locked' => throw BusinessRuleViolation::make('otp_attempts_exceeded', 'code'),
            default => throw BusinessRuleViolation::make('otp_invalid', 'code', ['attempts_remaining' => $outcome[1]]),
        };
    }

    /**
     * The fixed test code for a configured test number, or null. Defence in depth: besides the boot guard, the
     * environment is re-checked on every use, so production can never accept it even if the config leaks in.
     */
    public function testCodeFor(string $phone): ?string
    {
        $environments = array_values((array) config('integrations.non_production_environments', []));

        if (! app()->environment($environments) || ! config('identity.test_otp.enabled')) {
            return null;
        }

        $code = config('identity.test_otp.code');

        return is_string($code) && $code !== '' && in_array($phone, (array) config('identity.test_otp.phones', []), true)
            ? $code
            : null;
    }

    /**
     * @return array{0: OtpChallenge, 1: string} the stored challenge and the plain code (only ever handed to the SMS gateway)
     */
    private function issue(RequestOtpData $data, string $phoneHash, int $length, int $ttl, int $cooldown): array
    {
        $previous = $this->challenges->latestFor($phoneHash, $data->purpose);

        if ($previous !== null && $previous->consumed_at === null && $previous->invalidated_at === null
            && $previous->resend_available_at->gt(now())) {
            throw RateLimitedException::afterSeconds('otp_resend_too_soon', (int) ceil(now()->diffInSeconds($previous->resend_available_at, absolute: true)));
        }

        $testCode = $this->testCodeFor($data->phone);
        $code = $testCode ?? $this->generateCode($length);

        $challenge = DB::transaction(function () use ($data, $phoneHash, $ttl, $cooldown, $code, $testCode): OtpChallenge {
            $this->challenges->invalidateOpen($phoneHash, $data->purpose);

            return $this->challenges->create([
                'phone_hash' => $phoneHash,
                'phone_masked' => PhoneNumber::mask($data->phone),
                'purpose' => $data->purpose,
                'app' => $data->app,
                'code_hash' => $this->codeHash($phoneHash, $data->purpose, (string) $code),
                'attempts' => 0,
                'max_attempts' => $this->settings->int(SettingKey::OtpMaxAttempts),
                'delivery_status' => $testCode === null ? OtpDeliveryStatus::Sent : OtpDeliveryStatus::Skipped,
                'expires_at' => now()->addSeconds($ttl),
                'resend_available_at' => now()->addSeconds($cooldown),
                'ip' => $data->ip,
                'device_id' => $data->deviceId === null ? null : Str::limit($data->deviceId, 100, ''),
            ]);
        });

        return [$challenge, $code];
    }

    private function deliver(OtpChallenge $challenge, string $phone, string $code): void
    {
        if ($challenge->delivery_status === OtpDeliveryStatus::Skipped) {
            return;
        }

        try {
            $gateway = $this->container->make(SmsGateway::class);
            $gateway->send($phone, (string) __('api.otp.sms', ['code' => $code, 'minutes' => max(1, intdiv($this->settings->int(SettingKey::OtpTtlSeconds), 60))]));
            $this->challenges->update($challenge, ['delivery_driver' => $gateway->name()]);
        } catch (SmsDeliveryException $e) {
            // The exception message never carries the code or credentials; log the reason only.
            Log::warning('OTP SMS delivery failed.', ['reason' => $e->getMessage()]);
            $this->challenges->update($challenge, ['delivery_status' => OtpDeliveryStatus::Failed, 'invalidated_at' => now()]);

            throw ServiceUnavailableException::make('otp_delivery_failed');
        } catch (RuntimeException $e) {
            // No SMS driver configured: never pretend the code was sent.
            Log::error('OTP SMS gateway unavailable.', ['reason' => class_basename($e)]);
            $this->challenges->update($challenge, ['delivery_status' => OtpDeliveryStatus::Failed, 'invalidated_at' => now()]);

            throw ServiceUnavailableException::make('otp_delivery_failed');
        }
    }

    private function generateCode(int $length): string
    {
        $code = '';

        for ($i = 0; $i < $length; $i++) {
            $code .= (string) random_int(0, 9);
        }

        return $code;
    }

    private function codeHash(string $phoneHash, OtpPurpose $purpose, string $code): string
    {
        return hash_hmac('sha256', $phoneHash.'|'.$purpose->value.'|'.$code, PhoneNumber::key());
    }

    private function normalizeCode(string $code): string
    {
        $latin = strtr($code, ['٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9']);

        return (string) preg_replace('/\s+/', '', $latin);
    }
}
