<?php

namespace Tests\Concerns;

use App\Enums\Identity\DeletionRequestStatus;
use App\Enums\Identity\UserStatus;
use App\Models\AccountDeletionRequest;
use App\Models\CustomerProfile;
use App\Models\User;

/**
 * Fixtures for customer admin tests: a customer is a user with a customer profile.
 */
trait BuildsCustomers
{
    /**
     * @param array<string, mixed> $attributes
     */
    protected function customer(array $attributes = [], UserStatus $status = UserStatus::Active): User
    {
        $user = User::factory()->status($status)->create($attributes);
        CustomerProfile::query()->create(['user_id' => $user->id]);

        return $user;
    }

    protected function pendingDeletion(User $user, ?UserStatus $previous = null): AccountDeletionRequest
    {
        $request = AccountDeletionRequest::query()->create([
            'user_id' => $user->id,
            'status' => DeletionRequestStatus::Pending,
            'previous_user_status' => $previous ?? $user->status,
            'reason' => 'I no longer use the app',
            'requested_at' => now(),
        ]);
        $user->forceFill(['status' => UserStatus::DeletionPending])->save();

        return $request;
    }
}
