<?php

namespace App\Logging;

use Illuminate\Log\Logger;
use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\FormattableHandlerInterface;
use Monolog\Logger as MonologLogger;

/**
 * `tap` for log channels that write one JSON object per line (SRS 23.4). The correlation id (`request_id`), user-less request
 * context and queued-job context arrive through Laravel Context in `extra`; a processor adds the app/environment and redacts any
 * key that looks like a secret, so a careless `Log::info(..., ['token' => ...])` cannot leak it.
 */
final class StructuredLogging
{
    public function __invoke(Logger $logger): void
    {
        $monolog = $logger->getLogger();

        if (! $monolog instanceof MonologLogger) {
            return;
        }

        $monolog->pushProcessor(new RedactingProcessor((string) config('app.name'), (string) config('app.env')));

        foreach ($monolog->getHandlers() as $handler) {
            if ($handler instanceof FormattableHandlerInterface) {
                $handler->setFormatter(new JsonFormatter(JsonFormatter::BATCH_MODE_NEWLINES, true, true));
            }
        }
    }
}
