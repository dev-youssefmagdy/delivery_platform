<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Read model of Laravel's `failed_jobs` table (queue dead letters). Retry goes through the queue's failed-job provider, never
 * through this model. The payload and the exception text are not shown (they can hold personal data).
 *
 * @property int $id
 * @property string $uuid
 * @property string $connection
 * @property string $queue
 * @property string $payload
 * @property string $exception
 * @property Carbon $failed_at
 */
class FailedJob extends Model
{
    public $timestamps = false;

    protected $table = 'failed_jobs';

    /** @var list<string> */
    protected $hidden = ['payload', 'exception'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['failed_at' => 'datetime'];
    }
}
