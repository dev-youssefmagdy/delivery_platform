<?php

namespace App\Providers;

use App\Integrations\IntegrationGuard;
use App\Integrations\Sms\Contracts\SmsGateway;
use App\Integrations\Sms\Drivers\LogSmsGateway;
use App\Integrations\Sms\Drivers\SmsMisrGateway;
use App\Support\Auth\TestOtpGuard;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

/**
 * Binds integration contracts (app/Integrations/<Area>/Contracts) to the driver selected in
 * config/integrations.php, and refuses fake/log drivers outside local/testing at boot (ADR-12).
 */
class IntegrationServiceProvider extends ServiceProvider
{
    /**
     * area => [contract, [driver name => implementation]]. Filled by the milestones that add each adapter.
     *
     * @var array<string, array{0: class-string, 1: array<string, class-string>}>
     */
    protected array $integrations = [
        'sms' => [SmsGateway::class, ['log' => LogSmsGateway::class, 'smsmisr' => SmsMisrGateway::class]],
    ];

    public function register(): void
    {
        foreach ($this->integrations as $area => [$contract, $drivers]) {
            $this->app->singleton($contract, function () use ($area, $contract, $drivers): object {
                $driver = config("integrations.{$area}.driver");

                if (! is_string($driver) || ! isset($drivers[$driver])) {
                    throw new RuntimeException("Integration [{$area}] is not configured (contract {$contract}).");
                }

                return $this->app->make($drivers[$driver]);
            });
        }
    }

    public function boot(): void
    {
        $drivers = [];

        foreach ((array) config('integrations', []) as $area => $settings) {
            if (is_array($settings) && array_key_exists('driver', $settings)) {
                $drivers[(string) $area] = is_string($settings['driver']) ? $settings['driver'] : null;
            }
        }

        IntegrationGuard::assertAllowed(
            $drivers,
            (string) $this->app->environment(),
            array_values((array) config('integrations.non_production_drivers', [])),
            array_values((array) config('integrations.non_production_environments', [])),
        );

        // The fixed test OTP is a local/testing convenience: the application must not start anywhere else with it set.
        TestOtpGuard::assertAllowed(
            (bool) config('identity.test_otp.enabled'),
            config('identity.test_otp.code'),
            array_values((array) config('identity.test_otp.phones', [])),
            (string) $this->app->environment(),
            array_values((array) config('integrations.non_production_environments', [])),
        );
    }
}
