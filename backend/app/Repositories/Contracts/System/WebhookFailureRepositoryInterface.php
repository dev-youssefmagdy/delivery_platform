<?php

namespace App\Repositories\Contracts\System;

interface WebhookFailureRepositoryInterface
{
    /**
     * Rejected or failed payment webhook events since `$since`, with the most recent failure.
     *
     * @return array{count: int, last_at: \Illuminate\Support\Carbon|null, last_gateway: string|null, last_outcome: string|null}
     */
    public function summarySince(\DateTimeInterface $since): array;
}
