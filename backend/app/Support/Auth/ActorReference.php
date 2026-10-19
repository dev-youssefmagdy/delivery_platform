<?php

namespace App\Support\Auth;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

/**
 * (morph alias, id) of whoever performs an action; both null for guests/system.
 */
final readonly class ActorReference
{
    public function __construct(public ?string $type, public ?int $id) {}

    public static function of(?Authenticatable $actor): self
    {
        if ($actor === null) {
            return new self(null, null);
        }

        $id = $actor->getAuthIdentifier();

        return new self(
            $actor instanceof Model ? $actor->getMorphClass() : $actor::class,
            is_numeric($id) ? (int) $id : null,
        );
    }

    public function is(?string $type, ?int $id): bool
    {
        return $this->type === $type && $this->id === $id;
    }
}
