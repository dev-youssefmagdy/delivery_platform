<?php

namespace Tests\Feature\Support;

use App\Enums\Common\RateLimiterName;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Named rate limiters (Plan §4.4, SRS §20.7).
 */
class RateLimitersTest extends TestCase
{
    public function test_every_named_limiter_is_registered(): void
    {
        foreach (RateLimiterName::cases() as $name) {
            $this->assertNotNull(RateLimiter::limiter($name->value), "Limiter [{$name->value}] is not registered.");
        }
    }

    public function test_otp_request_limits_per_phone(): void
    {
        config(['rate_limits.otp-request.per_minute' => 2]);

        Route::middleware(['api', RateLimiterName::OtpRequest->middleware()])->post('api/v1/_test/otp', fn () => response()->noContent());

        $this->postJson('/api/v1/_test/otp', ['phone' => '+201000000001'])->assertNoContent();
        $this->postJson('/api/v1/_test/otp', ['phone' => '+201000000001'])->assertNoContent();
        $this->postJson('/api/v1/_test/otp', ['phone' => '+201000000001'])
            ->assertStatus(429)
            ->assertJsonPath('errors._code', 'too_many_requests');
    }
}
