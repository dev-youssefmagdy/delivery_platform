<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $actor_type
 * @property int $actor_id
 * @property string $key
 * @property string $route
 * @property string $request_hash
 * @property int $response_status
 * @property string $response_body
 * @property \Illuminate\Support\Carbon $expires_at
 */
class IdempotencyKey extends Model
{
    use MassPrunable;

    protected $fillable = [
        'actor_type', 'actor_id', 'key', 'route', 'request_hash', 'response_status', 'response_body', 'expires_at',
    ];

    /**
     * @return Builder<static>
     */
    public function prunable(): Builder
    {
        return static::query()->where('expires_at', '<', now());
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'actor_id' => 'integer',
            'response_status' => 'integer',
            'expires_at' => 'datetime',
        ];
    }
}
