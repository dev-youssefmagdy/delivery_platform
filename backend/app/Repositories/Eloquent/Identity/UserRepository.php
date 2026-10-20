<?php

namespace App\Repositories\Eloquent\Identity;

use App\Enums\Identity\UserStatus;
use App\Models\User;
use App\Repositories\Contracts\Identity\UserRepositoryInterface;
use App\Repositories\Eloquent\BaseRepository;
use Illuminate\Database\UniqueConstraintViolationException;
use LogicException;

/**
 * @extends BaseRepository<User>
 */
final class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    public function findByPhone(string $e164, bool $withTrashed = false): ?User
    {
        $query = $this->query();

        if ($withTrashed) {
            $query->withTrashed();
        }

        return $query->where('phone_e164', $e164)->first();
    }

    public function firstOrCreateByPhone(string $e164, array $attributes): array
    {
        $existing = $this->findByPhone($e164, withTrashed: true);

        if ($existing !== null) {
            return [$existing, false];
        }

        try {
            $user = new User($attributes);
            $user->phone_e164 = $e164;
            $user->status = UserStatus::Active;
            $user->save();

            return [$user, true];
        } catch (UniqueConstraintViolationException) {
            // A concurrent verification created the row first.
            return [$this->findByPhone($e164, withTrashed: true) ?? throw new LogicException('User vanished after a unique violation.'), false];
        }
    }

    public function markPhoneVerified(User $user): User
    {
        if ($user->phone_verified_at === null) {
            $user->forceFill(['phone_verified_at' => now()])->save();
        }

        return $user;
    }

    public function setStatus(User $user, UserStatus $status): User
    {
        $user->forceFill(['status' => $status])->save();

        return $user;
    }

    public function setPhoto(User $user, ?string $path): User
    {
        $user->forceFill(['photo_path' => $path])->save();

        return $user;
    }

    public function touchLastSeen(User $user, int $interval): void
    {
        $threshold = now()->subSeconds($interval);

        if ($user->last_seen_at !== null && $user->last_seen_at->gt($threshold)) {
            return;
        }

        $this->query()
            ->whereKey($user->getKey())
            ->where(static fn ($query) => $query->whereNull('last_seen_at')->orWhere('last_seen_at', '<=', $threshold))
            ->update(['last_seen_at' => now()]);
        $user->last_seen_at = now();
    }
}
