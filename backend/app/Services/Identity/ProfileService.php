<?php

namespace App\Services\Identity;

use App\DTOs\Identity\MeData;
use App\DTOs\Identity\UpdateProfileData;
use App\Enums\Identity\ClientApp;
use App\Exceptions\BusinessRuleViolation;
use App\Models\User;
use App\Repositories\Contracts\Identity\CustomerProfileRepositoryInterface;
use App\Repositories\Contracts\Identity\UserRepositoryInterface;
use App\Support\Media\ImageSanitizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * The signed-in user's own profile (`/me`). Everything here acts on the authenticated user only; the phone and the
 * status are never writable from a request.
 */
final class ProfileService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly CustomerProfileRepositoryInterface $customerProfiles,
        private readonly ConsentService $consents,
        private readonly AccountDeletionService $deletions,
        private readonly ImageSanitizer $images,
    ) {}

    public function me(User $user, ClientApp $app): MeData
    {
        if ($app === ClientApp::Customer) {
            $user->load('customerProfile');
        }

        return new MeData(
            user: $user,
            app: $app,
            pendingConsents: $this->consents->status($user, $app)->pending,
            deletionRequest: $this->deletions->pendingFor($user),
        );
    }

    /**
     * Where a user's stored photo lives, for the signed media route.
     *
     * @return array{disk: string, path: string}|null
     */
    public function photoLocation(string $uuid): ?array
    {
        $user = $this->users->findByUuid($uuid);

        return $user === null || $user->photo_path === null
            ? null
            : ['disk' => (string) config('identity.photo.disk', 'local'), 'path' => $user->photo_path];
    }

    public function update(User $user, ClientApp $app, UpdateProfileData $data): MeData
    {
        DB::transaction(function () use ($user, $app, $data): void {
            if ($data->user !== []) {
                $this->users->update($user, $data->user);
            }

            if ($app === ClientApp::Customer && $data->customer !== []) {
                $profile = $this->customerProfiles->firstOrCreateFor($user);
                $attributes = $data->customer;

                if (array_key_exists('marketing_opt_in', $attributes)) {
                    $optIn = (bool) $attributes['marketing_opt_in'];
                    $attributes['marketing_opt_in_at'] = $optIn ? ($profile->marketing_opt_in ? $profile->marketing_opt_in_at : now()) : null;
                }

                $this->customerProfiles->update($profile, $attributes);
            }
        });

        return $this->me($user->refresh(), $app);
    }

    /**
     * Stores the profile photo on the private disk (re-encoded, metadata stripped) and drops the previous one.
     *
     * @throws BusinessRuleViolation `photo_invalid`
     */
    public function replacePhoto(User $user, ClientApp $app, UploadedFile $file): MeData
    {
        try {
            $jpeg = $this->images->toJpeg((string) file_get_contents($file->getRealPath()), (int) config('identity.photo.max_edge_pixels', 1024));
        } catch (RuntimeException) {
            throw BusinessRuleViolation::make('photo_invalid', 'photo');
        }

        $disk = Storage::disk((string) config('identity.photo.disk', 'local'));
        $path = "users/{$user->uuid}/photo-".Str::lower(Str::random(16)).'.jpg';
        $previous = $user->photo_path;

        $disk->put($path, $jpeg);
        $this->users->setPhoto($user, $path);

        if ($previous !== null && $previous !== $path) {
            $disk->delete($previous);
        }

        return $this->me($user->refresh(), $app);
    }
}
