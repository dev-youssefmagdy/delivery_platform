<?php

namespace App\Services\System;

use App\DTOs\System\HealthItem;
use App\Enums\Common\HealthStatus;
use App\Enums\System\HealthArea;
use App\Repositories\Contracts\System\FailedJobRepositoryInterface;
use App\Repositories\Contracts\System\WebhookFailureRepositoryInterface;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Contracts\Queue\Factory as QueueFactory;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * The admin integration health page (SRS 16.15, 23.2): infrastructure probes (reusing HealthCheckService), queue depth and failed
 * jobs, Reverb, storage, every external adapter (configured / not configured / failing + last error code) and payment webhook
 * failures. Nothing here returns a secret: details are driver names, counts and ages; errors are safe codes.
 * A probe that throws becomes a `failing` row with the exception class; the real cause goes to the log.
 */
class IntegrationHealthService
{
    public function __construct(
        private readonly HealthCheckService $infrastructure,
        private readonly IntegrationStatusStore $failures,
        private readonly QueueFactory $queues,
        private readonly FailedJobRepositoryInterface $failedJobs,
        private readonly WebhookFailureRepositoryInterface $webhooks,
        private readonly FilesystemFactory $files,
    ) {}

    /**
     * @return list<HealthItem>
     */
    public function report(): array
    {
        $base = $this->infrastructure->check();
        $items = [];

        foreach ([HealthArea::Database, HealthArea::Redis, HealthArea::Scheduler] as $area) {
            $details = $base->details[$area->value] ?? [];
            unset($details['connection']);
            $items[] = new HealthItem($area, $base->checks[$area->value] ?? HealthStatus::Failing, $this->scalars($details));
        }

        $items[] = $this->guarded(HealthArea::Queue, fn (): HealthItem => $this->queueDepth());
        $items[] = $this->guarded(HealthArea::FailedJobs, fn (): HealthItem => $this->failedJobsItem());
        $items[] = $this->guarded(HealthArea::Reverb, fn (): HealthItem => $this->reverb());
        $items[] = $this->guarded(HealthArea::Storage, fn (): HealthItem => $this->storage());

        foreach ([HealthArea::Maps, HealthArea::Sms, HealthArea::Payments, HealthArea::Push, HealthArea::FileScanner, HealthArea::Email, HealthArea::ErrorTracker] as $area) {
            $items[] = $this->guarded($area, fn (): HealthItem => $this->adapter($area));
        }

        $items[] = $this->guarded(HealthArea::PaymentWebhooks, fn (): HealthItem => $this->paymentWebhooks());

        return $items;
    }

    /**
     * Worst status across the rows that are configured (not-configured rows do not make the platform unhealthy).
     *
     * @param list<HealthItem> $items
     */
    public function overall(array $items): HealthStatus
    {
        $statuses = array_map(static fn (HealthItem $item): HealthStatus => $item->status, $items);

        return match (true) {
            in_array(HealthStatus::Failing, $statuses, true) => HealthStatus::Failing,
            in_array(HealthStatus::Degraded, $statuses, true) => HealthStatus::Degraded,
            default => HealthStatus::Ok,
        };
    }

    private function queueDepth(): HealthItem
    {
        $depths = [];
        $status = HealthStatus::Ok;

        foreach ((array) config('health.queues', ['default']) as $name) {
            $size = $this->queues->connection()->size((string) $name);
            $depths['depth_'.$name] = $size;

            if ($size >= (int) config('health.queue_degraded_depth', 500)) {
                $status = HealthStatus::Degraded;
            }
        }

        return new HealthItem(HealthArea::Queue, $status, $depths);
    }

    private function failedJobsItem(): HealthItem
    {
        $count = $this->failedJobs->count();

        return new HealthItem(
            HealthArea::FailedJobs,
            $count >= max(1, (int) config('health.failed_jobs_degraded_count', 1)) ? HealthStatus::Degraded : HealthStatus::Ok,
            ['failed' => $count],
        );
    }

    private function reverb(): HealthItem
    {
        if (config('broadcasting.default') !== 'reverb') {
            return new HealthItem(HealthArea::Reverb, HealthStatus::NotConfigured);
        }

        $host = (string) config('reverb.servers.reverb.host', '127.0.0.1');
        $host = in_array($host, ['0.0.0.0', '::', ''], true) ? '127.0.0.1' : $host;
        $port = (int) config('reverb.servers.reverb.port', 8080);

        return new HealthItem(HealthArea::Reverb, $this->reachable($host, $port) ? HealthStatus::Ok : HealthStatus::Failing, ['port' => $port]);
    }

    private function storage(): HealthItem
    {
        $diskName = (string) config('filesystems.default', 'local');
        $disk = $this->files->disk($diskName);
        $path = 'health/probe-'.bin2hex(random_bytes(6)).'.txt';

        $disk->put($path, 'ok');
        $ok = $disk->get($path) === 'ok';
        $disk->delete($path);

        return new HealthItem(HealthArea::Storage, $ok ? HealthStatus::Ok : HealthStatus::Failing, ['disk' => $diskName]);
    }

    private function adapter(HealthArea $area): HealthItem
    {
        $key = (string) $area->integrationKey();
        $driver = config("integrations.{$key}.driver");
        $driver = is_string($driver) && $driver !== '' ? $driver : null;

        if ($driver === null) {
            return new HealthItem($area, HealthStatus::NotConfigured);
        }

        $details = ['driver' => $driver];
        $failure = $this->failures->lastFailure($area);
        $recent = $failure !== null && $failure['at']->greaterThan(now()->subMinutes((int) config('health.adapter_failure_window_minutes', 15)));

        if (! $this->credentialsPresent($area, $driver)) {
            return new HealthItem($area, HealthStatus::NotConfigured, $details + ['reason' => 'credentials_missing'], $failure['code'] ?? null, $failure['at'] ?? null);
        }

        if ($area === HealthArea::FileScanner && $driver === 'clamav') {
            $reachable = $this->reachable((string) config('integrations.file_scanner.clamav.host'), (int) config('integrations.file_scanner.clamav.port'));

            if (! $reachable) {
                return new HealthItem($area, HealthStatus::Failing, $details, 'ConnectionRefused', now());
            }
        }

        return new HealthItem($area, $recent ? HealthStatus::Failing : HealthStatus::Ok, $details, $failure['code'] ?? null, $failure['at'] ?? null);
    }

    /**
     * Whether the credentials the driver needs are set. Only presence is checked; values are never read out of this method.
     */
    private function credentialsPresent(HealthArea $area, string $driver): bool
    {
        $filled = static fn (string ...$keys): bool => array_reduce($keys, static fn (bool $carry, string $key): bool => $carry && filled(config($key)), true);

        return match ([$area, $driver]) {
            [HealthArea::Maps, 'google'] => $filled('maps.google.api_key'),
            [HealthArea::Sms, 'smsmisr'] => $filled('services.smsmisr.username', 'services.smsmisr.password', 'services.smsmisr.sender'),
            [HealthArea::Payments, 'paymob'] => $filled('payments.paymob.api_key', 'payments.paymob.secret_key', 'payments.paymob.hmac_secret'),
            [HealthArea::Push, 'fcm'] => $filled('integrations.push.fcm.project_id', 'integrations.push.fcm.credentials')
                && is_file((string) config('integrations.push.fcm.credentials')),
            [HealthArea::ErrorTracker, 'sentry'] => \App\Integrations\Errors\Drivers\SentryErrorTracker::endpoint((string) config('integrations.error_tracker.dsn')) !== null,
            default => true,
        };
    }

    private function paymentWebhooks(): HealthItem
    {
        $hours = (int) config('health.webhook_window_hours', 24);
        $summary = $this->webhooks->summarySince(now()->subHours($hours));

        return new HealthItem(
            HealthArea::PaymentWebhooks,
            $summary['count'] >= max(1, (int) config('health.webhook_degraded_count', 1)) ? HealthStatus::Degraded : HealthStatus::Ok,
            ['failures' => $summary['count'], 'window_hours' => $hours, 'last_gateway' => $summary['last_gateway']],
            $summary['last_outcome'],
            $summary['last_at'],
        );
    }

    /**
     * @param callable(): HealthItem $probe
     */
    private function guarded(HealthArea $area, callable $probe, string $label = ''): HealthItem
    {
        try {
            return $probe();
        } catch (Throwable $e) {
            Log::warning('Health probe failed.', ['area' => $area->value.($label === '' ? '' : ':'.$label), 'exception' => $e]);

            return new HealthItem($area, HealthStatus::Failing, [], class_basename($e), now());
        }
    }

    protected function reachable(string $host, int $port): bool
    {
        $socket = @fsockopen($host, $port, $errno, $error, (float) config('health.socket_timeout_seconds', 1.5));

        if ($socket === false) {
            return false;
        }

        fclose($socket);

        return true;
    }

    /**
     * @param array<string, mixed> $details
     * @return array<string, scalar|null>
     */
    private function scalars(array $details): array
    {
        return array_filter($details, static fn (mixed $v): bool => is_scalar($v) || $v === null);
    }
}
