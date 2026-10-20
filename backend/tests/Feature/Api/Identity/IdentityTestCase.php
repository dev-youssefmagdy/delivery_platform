<?php

namespace Tests\Feature\Api\Identity;

use App\Enums\Content\ContentStatus;
use App\Enums\Policy\PolicyType;
use App\Integrations\Sms\Contracts\SmsGateway;
use App\Models\PolicyVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ArraySmsGateway;
use Tests\TestCase;

abstract class IdentityTestCase extends TestCase
{
    use RefreshDatabase;

    protected ArraySmsGateway $sms;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sms = new ArraySmsGateway;
        $this->app->instance(SmsGateway::class, $this->sms);
    }

    /**
     * @param array<string, string> $headers
     */
    protected function requestOtp(string $phone = '+201001234567', string $app = 'customer', array $headers = [], array $extra = []): \Illuminate\Testing\TestResponse
    {
        return $this->postJson('/api/v1/auth/otp/request', ['phone' => $phone] + $extra, ['X-App' => $app] + $headers);
    }

    protected function verifyOtp(string $phone, string $code, string $app = 'customer', string $device = 'dev-1'): \Illuminate\Testing\TestResponse
    {
        return $this->postJson('/api/v1/auth/otp/verify', [
            'phone' => $phone, 'code' => $code,
            'device' => ['device_id' => $device, 'platform' => 'android', 'app_version' => '1.0.0'],
        ], ['X-App' => $app]);
    }

    /** Full sign-in; returns the bearer token. */
    protected function signIn(string $phone = '+201001234567', string $app = 'customer', string $device = 'dev-1'): string
    {
        $this->requestOtp($phone, $app)->assertOk();

        return (string) $this->verifyOtp($phone, $this->sms->lastCode(), $app, $device)->assertOk()->json('data.token');
    }

    /**
     * @return array<string, string>
     */
    protected function bearer(string $token): array
    {
        // The Sanctum guard caches the resolved user for the whole test process; real requests never share it.
        $this->app['auth']->forgetGuards();

        return ['Authorization' => 'Bearer '.$token];
    }

    protected function publishPolicy(PolicyType $type, int $version = 1): PolicyVersion
    {
        return PolicyVersion::factory()->create([
            'type' => $type, 'version' => $version, 'status' => ContentStatus::Published, 'published_at' => now(),
        ]);
    }
}
