<?php

namespace Tests\Feature\Api\Identity;

use App\Enums\Identity\UserStatus;
use App\Enums\Settings\SettingKey;
use App\Models\OtpChallenge;
use App\Models\User;
use App\Services\Settings\SettingsService;
use Illuminate\Support\Facades\Log;

class OtpFlowTest extends IdentityTestCase
{
    public function test_request_requires_the_app_header_and_a_valid_phone(): void
    {
        $this->postJson('/api/v1/auth/otp/request', ['phone' => '+201001234567'])->assertStatus(422)->assertJsonValidationErrorFor('app', 'errors');
        $this->requestOtp('12345')->assertStatus(422)->assertJsonPath('errors._code', 'validation_failed');
        $this->requestOtp(app: 'admin')->assertStatus(422);
    }

    public function test_otp_is_hashed_never_in_the_response_and_sent_by_sms(): void
    {
        $response = $this->requestOtp()->assertOk();

        $code = $this->sms->lastCode();
        $this->assertSame(6, strlen($code));
        $this->assertStringNotContainsString($code, (string) $response->getContent());
        $challenge = OtpChallenge::query()->firstOrFail();
        $this->assertStringNotContainsString($code, json_encode($challenge->getAttributes(), JSON_THROW_ON_ERROR));
        $this->assertSame('+20••••••4567', $challenge->phone_masked);
        $response->assertJsonPath('data.length', 6)->assertJsonPath('data.expires_in', 300)->assertJsonPath('data.resend_in', 60);
    }

    public function test_request_answer_is_the_same_for_known_and_unknown_phones(): void
    {
        User::factory()->create(['phone_e164' => '+201001111111']);

        $known = $this->requestOtp('+201001111111')->assertOk()->json();
        $unknown = $this->requestOtp('+201002222222')->assertOk()->json();

        $this->assertSame($known, $unknown);
    }

    public function test_resend_cooldown_is_enforced_with_retry_after(): void
    {
        $this->requestOtp()->assertOk();
        $this->requestOtp()->assertStatus(429)->assertJsonPath('errors._code', 'otp_resend_too_soon')->assertHeader('Retry-After');

        $this->travel(61)->seconds();
        $this->requestOtp()->assertOk();
        $this->assertSame(1, OtpChallenge::query()->whereNotNull('invalidated_at')->count());
    }

    public function test_the_cooldown_comes_from_settings(): void
    {
        app(SettingsService::class)->set(SettingKey::OtpResendCooldownSeconds, 10);
        $this->requestOtp()->assertOk();
        $this->travel(11)->seconds();
        $this->requestOtp()->assertOk();
    }

    public function test_ip_throttle_blocks_after_the_minute_limit(): void
    {
        foreach (['+201000000001', '+201000000002', '+201000000003'] as $phone) {
            $this->requestOtp($phone)->assertOk();
        }

        $this->requestOtp('+201000000004')->assertStatus(429)->assertJsonPath('errors._code', 'too_many_requests');
    }

    public function test_device_throttle_applies_across_numbers_and_ips(): void
    {
        foreach (['+201000000001', '+201000000002', '+201000000003'] as $i => $phone) {
            $this->requestOtp($phone, extra: ['device_id' => 'same-device'], headers: ['REMOTE_ADDR' => "10.0.0.{$i}"])->assertOk();
        }

        $this->withServerVariables(['REMOTE_ADDR' => '10.9.9.9'])->requestOtp('+201000000009', extra: ['device_id' => 'same-device'])
            ->assertStatus(429);
    }

    public function test_phone_throttle_ignores_formatting_tricks(): void
    {
        foreach (['+201001234567', '+20 100 123 4567', '0020 100-123-4567'] as $i => $variant) {
            $response = $this->withServerVariables(['REMOTE_ADDR' => "10.1.0.{$i}"])->requestOtp($variant);
            $this->assertContains($response->status(), [200, 429]);
        }

        $this->withServerVariables(['REMOTE_ADDR' => '10.1.0.9'])->requestOtp('+201001234567')->assertStatus(429);
    }

    public function test_verify_signs_in_creates_the_user_and_returns_a_scoped_token(): void
    {
        $this->requestOtp()->assertOk();

        $response = $this->verifyOtp('+201001234567', $this->sms->lastCode())->assertOk();

        $response->assertJsonPath('data.is_new_user', true)
            ->assertJsonPath('data.abilities', ['customer'])
            ->assertJsonPath('data.user.profile_complete', false)
            ->assertJsonPath('data.user.status', 'active')
            ->assertJsonPath('data.user.phone', '+201001234567');
        $user = User::query()->firstOrFail();
        $this->assertNotNull($user->phone_verified_at);
        $this->assertNotNull($user->customerProfile);
        $this->assertDatabaseHas('user_devices', ['user_id' => $user->id, 'device_id' => 'dev-1', 'app' => 'customer']);
        $this->assertNotNull(OtpChallenge::query()->firstOrFail()->consumed_at);

        // A code works once.
        $this->verifyOtp('+201001234567', $this->sms->lastCode())->assertStatus(422)->assertJsonPath('errors._code', 'otp_invalid');
    }

    public function test_second_sign_in_returns_the_same_user_and_replaces_the_device_token(): void
    {
        $first = $this->signIn();
        $this->travel(61)->seconds();
        $second = $this->signIn();

        $this->assertSame(1, User::query()->count());
        $this->assertSame(1, User::query()->firstOrFail()->tokens()->count());
        $this->assertNotSame($first, $second);
    }

    public function test_wrong_code_counts_attempts_and_locks_the_challenge(): void
    {
        $this->requestOtp()->assertOk();
        $good = $this->sms->lastCode();
        $bad = $good === '000000' ? '111111' : '000000';

        $this->verifyOtp('+201001234567', $bad)->assertStatus(422)->assertJsonPath('errors._code', 'otp_invalid')->assertJsonPath('meta.attempts_remaining', 4);
        foreach (range(1, 3) as $_) {
            $this->travel(20)->seconds();
            $this->verifyOtp('+201001234567', $bad)->assertStatus(422);
        }
        $this->travel(20)->seconds();
        $this->verifyOtp('+201001234567', $bad)->assertStatus(422)->assertJsonPath('errors._code', 'otp_attempts_exceeded');

        // Even the right code no longer works once locked.
        $this->travel(20)->seconds();
        $this->verifyOtp('+201001234567', $good)->assertStatus(422)->assertJsonPath('errors._code', 'otp_attempts_exceeded');
        $this->assertSame(0, User::query()->count());
    }

    public function test_expired_code_is_rejected(): void
    {
        $this->requestOtp()->assertOk();
        $code = $this->sms->lastCode();

        $this->travel(301)->seconds();

        $this->verifyOtp('+201001234567', $code)->assertStatus(422)->assertJsonPath('errors._code', 'otp_expired');
    }

    public function test_verify_without_a_challenge_looks_like_a_wrong_code(): void
    {
        $this->verifyOtp('+201009999999', '123456')->assertStatus(422)->assertJsonPath('errors._code', 'otp_invalid');
    }

    public function test_arabic_indic_digits_are_accepted(): void
    {
        $this->requestOtp('+٢٠١٠٠١٢٣٤٥٦٧')->assertOk();
        $arabic = strtr($this->sms->lastCode(), ['0' => '٠', '1' => '١', '2' => '٢', '3' => '٣', '4' => '٤', '5' => '٥', '6' => '٦', '7' => '٧', '8' => '٨', '9' => '٩']);

        $this->verifyOtp('+201001234567', $arabic)->assertOk();
    }

    public function test_suspended_and_deleted_accounts_get_typed_errors_after_a_valid_code(): void
    {
        User::factory()->status(UserStatus::Suspended)->create(['phone_e164' => '+201001111111']);
        User::factory()->create(['phone_e164' => '+201002222222'])->delete();
        User::factory()->status(UserStatus::DeletionPending)->create(['phone_e164' => '+201003333333']);

        $this->requestOtp('+201001111111')->assertOk();
        $this->verifyOtp('+201001111111', $this->sms->lastCode())->assertStatus(403)->assertJsonPath('errors._code', 'account_suspended');

        $this->requestOtp('+201002222222')->assertOk();
        $this->verifyOtp('+201002222222', $this->sms->lastCode())->assertStatus(403)->assertJsonPath('errors._code', 'account_deleted');

        $this->requestOtp('+201003333333')->assertOk();
        $this->verifyOtp('+201003333333', $this->sms->lastCode())->assertOk()->assertJsonPath('data.user.status', 'deletion_pending');
    }

    public function test_sms_failure_is_reported_not_faked(): void
    {
        $this->sms->fail = true;

        $this->requestOtp()->assertStatus(503)->assertJsonPath('errors._code', 'otp_delivery_failed');
        $this->assertSame('failed', OtpChallenge::query()->firstOrFail()->delivery_status->value);

        // A failed delivery does not start a cooldown.
        $this->sms->fail = false;
        $this->requestOtp()->assertOk();
    }

    public function test_the_log_driver_writes_only_to_the_otp_dev_channel(): void
    {
        $path = storage_path('framework/testing/otp_dev.log');
        @unlink($path);
        config(['logging.channels.otp_dev.path' => $path]);
        Log::forgetChannel('otp_dev');
        $this->app->instance(\App\Integrations\Sms\Contracts\SmsGateway::class, new \App\Integrations\Sms\Drivers\LogSmsGateway);

        $response = $this->requestOtp()->assertOk();

        $this->assertMatchesRegularExpression('/\+201001234567: .*\d{6}/', (string) file_get_contents($path));
        preg_match('/(\d{6})/', (string) file_get_contents($path), $m);
        $this->assertStringNotContainsString($m[1], (string) $response->getContent());
        @unlink($path);
    }
}
