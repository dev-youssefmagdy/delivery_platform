<?php

namespace Tests\Unit\Integrations;

use App\Integrations\IntegrationGuard;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class IntegrationGuardTest extends TestCase
{
    private const FAKES = ['fake', 'log'];

    private const ALLOWED = ['local', 'testing'];

    public function test_fake_drivers_are_allowed_in_local_and_testing(): void
    {
        IntegrationGuard::assertAllowed(['sms' => 'log', 'maps' => 'fake'], 'local', self::FAKES, self::ALLOWED);
        IntegrationGuard::assertAllowed(['sms' => 'log'], 'testing', self::FAKES, self::ALLOWED);

        $this->addToAssertionCount(1);
    }

    public function test_real_or_unconfigured_drivers_are_allowed_in_production(): void
    {
        IntegrationGuard::assertAllowed(['sms' => 'twilio', 'maps' => null], 'production', self::FAKES, self::ALLOWED);

        $this->addToAssertionCount(1);
    }

    public function test_fake_drivers_throw_outside_local_and_testing(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('sms=LOG');

        IntegrationGuard::assertAllowed(['sms' => 'LOG', 'maps' => 'google'], 'staging', self::FAKES, self::ALLOWED);
    }
}
