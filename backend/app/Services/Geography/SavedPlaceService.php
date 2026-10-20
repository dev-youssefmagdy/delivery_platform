<?php

namespace App\Services\Geography;

use App\DTOs\Geography\SavedPlaceData;
use App\Exceptions\BusinessRuleViolation;
use App\Models\SavedPlace;
use App\Models\User;
use App\Repositories\Contracts\Geography\SavedPlaceRepositoryInterface;
use App\Repositories\Contracts\Identity\UserRepositoryInterface;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * A customer's saved places. Every operation is scoped by the owner's id, so another user's place is simply
 * "not found" (no IDOR, no existence leak). One `home` and one `work` per user, a configurable maximum in total;
 * the checks run under a lock on the user row so concurrent requests cannot exceed them.
 */
final class SavedPlaceService
{
    public function __construct(
        private readonly SavedPlaceRepositoryInterface $places,
        private readonly UserRepositoryInterface $users,
        private readonly DatabaseManager $db,
    ) {}

    /**
     * @return Collection<int, SavedPlace>
     */
    public function list(User $user): Collection
    {
        return $this->places->forUser($user->id);
    }

    public function create(User $user, SavedPlaceData $data): SavedPlace
    {
        return $this->db->transaction(function () use ($user, $data): SavedPlace {
            $this->users->lockById($user->id);

            if ($this->places->countForUser($user->id) >= (int) config('geo.saved_places.max', 20)) {
                throw BusinessRuleViolation::make('saved_place_limit_reached');
            }

            if ($data->type->isUnique() && $this->places->typeTaken($user->id, $data->type)) {
                throw BusinessRuleViolation::make('saved_place_type_taken', 'type');
            }

            /** @var SavedPlace $place */
            $place = $this->places->create($data->attributes() + ['user_id' => $user->id]);

            return $place;
        });
    }

    public function update(User $user, string $uuid, SavedPlaceData $data): SavedPlace
    {
        return $this->db->transaction(function () use ($user, $uuid, $data): SavedPlace {
            $this->users->lockById($user->id);
            $place = $this->owned($user, $uuid);

            if ($data->type->isUnique() && $this->places->typeTaken($user->id, $data->type, $place->id)) {
                throw BusinessRuleViolation::make('saved_place_type_taken', 'type');
            }

            $this->places->update($place, $data->attributes());

            return $place;
        });
    }

    public function delete(User $user, string $uuid): void
    {
        $this->db->transaction(function () use ($user, $uuid): void {
            $this->places->delete($this->owned($user, $uuid));
        });
    }

    private function owned(User $user, string $uuid): SavedPlace
    {
        return $this->places->findForUser($user->id, $uuid) ?? throw (new ModelNotFoundException)->setModel(SavedPlace::class);
    }
}
