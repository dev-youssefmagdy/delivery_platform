<?php

namespace App\Services\System;

use App\DTOs\System\HealthReport;
use App\Enums\Common\HealthStatus;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Queue\Factory as QueueFactory;
use Illuminate\Database\DatabaseManager;
use Illuminate\Redis\RedisManager;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Infrastructure probes for /api/v1/health and, later, the admin system health page (SRS §23.2).
 * Probes are connection checks, not data queries. Failures are logged with their real cause; callers only
 * receive a status.
 */
final class HealthCheckService
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly RedisManager $redis,
        private readonly QueueFactory $queue,
        private readonly CacheRepository $cache,
    ) {}

    public function check(): HealthReport
    {
        $checks = [];
        $details = [];

        foreach (['database' => fn (): array => $this->database(), 'redis' => fn (): array => $this->redis(), 'queue' => fn (): array => $this->queue(), 'scheduler' => fn (): array => $this->scheduler()] as $name => $probe) {
            $started = hrtime(true);

            try {
                [$status, $detail] = $probe();
            } catch (Throwable $e) {
                Log::warning("Health check [{$name}] failed.", ['exception' => $e]);
                [$status, $detail] = [HealthStatus::Failing, ['error' => class_basename($e)]];
            }

            $checks[$name] = $status;
            $details[$name] = $detail + ['duration_ms' => round((hrtime(true) - $started) / 1_000_000, 2)];
        }

        return new HealthReport($checks, $details);
    }

    /**
     * @return array{0: HealthStatus, 1: array<string, mixed>}
     */
    private function database(): array
    {
        $connection = $this->db->connection();
        $connection->getPdo();
        $connection->selectOne('select 1 as ok');

        return [HealthStatus::Ok, ['connection' => $connection->getName()]];
    }

    /**
     * @return array{0: HealthStatus, 1: array<string, mixed>}
     */
    private function redis(): array
    {
        $this->redis->connection()->command('ping');

        return [HealthStatus::Ok, []];
    }

    /**
     * @return array{0: HealthStatus, 1: array<string, mixed>}
     */
    private function queue(): array
    {
        $connection = $this->queue->connection();

        return [HealthStatus::Ok, ['connection' => $connection->getConnectionName(), 'default_size' => $connection->size()]];
    }

    /**
     * @return array{0: HealthStatus, 1: array<string, mixed>}
     */
    private function scheduler(): array
    {
        $lastBeat = $this->cache->get((string) config('health.heartbeat_cache_key'));

        if (! is_numeric($lastBeat)) {
            return [HealthStatus::Failing, ['heartbeat_age_seconds' => null]];
        }

        $age = max(0, now()->getTimestamp() - (int) $lastBeat);
        $status = $age <= (int) config('health.heartbeat_max_age_seconds', 180) ? HealthStatus::Ok : HealthStatus::Failing;

        return [$status, ['heartbeat_age_seconds' => $age]];
    }
}
