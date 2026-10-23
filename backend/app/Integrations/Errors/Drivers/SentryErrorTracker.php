<?php

namespace App\Integrations\Errors\Drivers;

use App\Enums\System\HealthArea;
use App\Integrations\Errors\Contracts\ErrorTracker;
use App\Services\System\IntegrationStatusStore;
use Illuminate\Http\Client\Factory as Http;
use Throwable;

/**
 * Sends an exception to Sentry through its HTTP store endpoint (no SDK dependency). The event carries the exception class, a
 * trimmed message, file and line, a short stack of file:line frames, release, environment and the `request_id` tag. Failures
 * are recorded for the health page and never thrown: error tracking must not break a request or a job.
 */
final class SentryErrorTracker implements ErrorTracker
{
    public function __construct(
        private readonly Http $http,
        private readonly string $dsn,
        private readonly string $environment,
        private readonly ?string $release,
        private readonly float $sampleRate,
    ) {}

    public function report(Throwable $exception, ?string $requestId = null): void
    {
        $endpoint = self::endpoint($this->dsn);

        if ($endpoint === null || ($this->sampleRate < 1.0 && mt_rand() / mt_getrandmax() > $this->sampleRate)) {
            return;
        }

        try {
            $response = $this->http->timeout(3)->withHeaders(['X-Sentry-Auth' => $endpoint['auth']])->asJson()->post($endpoint['url'], $this->event($exception, $requestId));

            if ($response->failed()) {
                IntegrationStatusStore::failed(HealthArea::ErrorTracker, 'http_'.$response->status());

                return;
            }

            IntegrationStatusStore::succeeded(HealthArea::ErrorTracker);
        } catch (Throwable $e) {
            IntegrationStatusStore::failed(HealthArea::ErrorTracker, $e);
        }
    }

    /**
     * Builds the store endpoint and auth header from a DSN (https://KEY@HOST/PROJECT_ID). Null when the DSN is not valid.
     *
     * @return array{url: string, auth: string}|null
     */
    public static function endpoint(string $dsn): ?array
    {
        $parts = parse_url($dsn);
        $project = isset($parts['path']) ? trim($parts['path'], '/') : '';

        if (! is_array($parts) || ! isset($parts['scheme'], $parts['host'], $parts['user']) || $project === '' || ! ctype_digit($project) || $parts['scheme'] !== 'https') {
            return null;
        }

        $port = isset($parts['port']) ? ':'.$parts['port'] : '';

        return [
            'url' => "https://{$parts['host']}{$port}/api/{$project}/store/",
            'auth' => 'Sentry sentry_version=7, sentry_client=delivery-platform/1.0, sentry_key='.$parts['user'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function event(Throwable $exception, ?string $requestId): array
    {
        $frames = [];

        foreach (array_slice($exception->getTrace(), 0, 25) as $frame) {
            $frames[] = ['filename' => $frame['file'] ?? '[internal]', 'lineno' => $frame['line'] ?? 0, 'function' => $frame['function']];
        }

        return array_filter([
            'event_id' => bin2hex(random_bytes(16)),
            'timestamp' => gmdate('Y-m-d\TH:i:s'),
            'platform' => 'php',
            'level' => 'error',
            'environment' => $this->environment,
            'release' => $this->release,
            'tags' => array_filter(['request_id' => $requestId]),
            'exception' => ['values' => [[
                'type' => $exception::class,
                'value' => mb_substr($exception->getMessage(), 0, 300),
                'stacktrace' => ['frames' => array_reverse($frames)],
            ]]],
        ], static fn (mixed $v): bool => $v !== null && $v !== []);
    }
}
