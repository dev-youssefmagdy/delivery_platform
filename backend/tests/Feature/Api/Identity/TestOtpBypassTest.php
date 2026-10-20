<?php

namespace Tests\Feature\Api\Identity;

use App\Models\User;
use App\Services\Identity\OtpService;
use App\Support\Auth\TestOtpGuard;
use RuntimeException;

class TestOtpBypassTest extends IdentityTestCase
{
    private const PHONE = '+201000000001';

    private function enableTestOtp(): void
    {
        config(['identity.test_otp' => ['enabled' => true, 'code' => '123456', 'phones' => [self::PHONE]]]);
    }

    public function test_the_fixed_code_works_for_a_test_number_in_testing(): void
    {
        $this->enableTestOtp();

        $this->requestOtp(self::PHONE)->assertOk();
        $this->assertSame([], $this->sms->sent, 'No SMS is sent for a test number.');
        $this->verifyOtp(self::PHONE, '123456')->assertOk();
        $this->assertSame(1, User::query()->count());
    }

    public function test_other_numbers_never_get_the_fixed_code(): void
    {
        $this->enableTestOtp();

        $this->requestOtp('+201000000002')->assertOk();
        $this->verifyOtp('+201000000002', '123456')->assertStatus(422);
    }

    public function test_the_fixed_code_is_impossible_in_production(): void
    {
        $this->enableTestOtp();
        $this->app['env'] = 'production';

        $this->assertNull(app(OtpService::class)->testCodeFor(self::PHONE));
    }

    public function test_boot_guard_refuses_test_otp_settings_outside_local_and_testing(): void
    {
        $allowed = ['local', 'testing'];

        TestOtpGuard::assertAllowed(true, '123456', [self::PHONE], 'testing', $allowed);
        TestOtpGuard::assertAllowed(false, null, [], 'production', $allowed);

        foreach ([[true, null, []], [false, '123456', []], [false, null, [self::PHONE]]] as [$enabled, $code, $phones]) {
            foreach (['production', 'staging'] as $environment) {
                try {
                    TestOtpGuard::assertAllowed($enabled, $code, $phones, $environment, $allowed);
                    $this->fail("Guard accepted test OTP config in {$environment}.");
                } catch (RuntimeException $e) {
                    $this->assertStringContainsString('test OTP', $e->getMessage());
                }
            }
        }
    }

    public function test_the_application_refuses_to_boot_in_production_with_the_bypass_configured(): void
    {
        $this->enableTestOtp();
        $this->app['env'] = 'production';

        $this->expectException(RuntimeException::class);
        (new \App\Providers\IntegrationServiceProvider($this->app))->boot();
    }

    public function test_a_client_parameter_cannot_activate_the_bypass(): void
    {
        $this->requestOtp(self::PHONE, extra: ['test_otp' => true, 'bypass' => '123456'])->assertOk();
        $this->postJson('/api/v1/auth/otp/verify', [
            'phone' => self::PHONE, 'code' => '123456', 'test_otp' => true,
            'device' => ['device_id' => 'd', 'platform' => 'ios'],
        ], ['X-App' => 'customer', 'X-Test-Otp' => '1'])->assertStatus(422);
    }
}
