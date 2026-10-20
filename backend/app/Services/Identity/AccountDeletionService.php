<?php

namespace App\Services\Identity;

use App\Enums\Identity\DeletionRequestStatus;
use App\Enums\Identity\UserStatus;
use App\Exceptions\BusinessRuleViolation;
use App\Exceptions\ConflictException;
use App\Models\AccountDeletionRequest;
use App\Models\User;
use App\Repositories\Contracts\Identity\AccountDeletionRequestRepositoryInterface;
use App\Repositories\Contracts\Identity\UserRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Account deletion requests (SRS §6.2, §13.3). The request only marks the account `deletion_pending`; an admin
 * processes it later (P1.6) after legal retention is checked.
 */
final class AccountDeletionService
{
    public function __construct(
        private readonly AccountDeletionRequestRepositoryInterface $requests,
        private readonly UserRepositoryInterface $users,
    ) {}

    /**
     * @throws ConflictException `deletion_request_exists` when one is already pending
     */
    public function request(User $user, ?string $reason): AccountDeletionRequest
    {
        return DB::transaction(function () use ($user, $reason): AccountDeletionRequest {
            $locked = $this->users->lockById($user->id) ?? $user;

            if ($this->requests->pendingFor($locked) !== null) {
                throw ConflictException::make('deletion_request_exists');
            }

            $request = $this->requests->create([
                'user_id' => $locked->id,
                'status' => DeletionRequestStatus::Pending,
                'previous_user_status' => $locked->status,
                'reason' => $reason,
                'requested_at' => now(),
            ]);

            $this->users->setStatus($user, UserStatus::DeletionPending);

            return $request;
        });
    }

    /**
     * @throws BusinessRuleViolation `deletion_request_not_pending`
     */
    public function cancel(User $user, string $uuid): AccountDeletionRequest
    {
        return DB::transaction(function () use ($user, $uuid): AccountDeletionRequest {
            $this->users->lockById($user->id);
            $request = $this->requests->findForUser($user, $uuid) ?? throw (new ModelNotFoundException)->setModel(AccountDeletionRequest::class);

            if ($request->status !== DeletionRequestStatus::Pending) {
                throw BusinessRuleViolation::make('deletion_request_not_pending');
            }

            $this->requests->update($request, ['status' => DeletionRequestStatus::Cancelled, 'processed_at' => now()]);
            $this->users->setStatus($user, $request->previous_user_status === UserStatus::DeletionPending ? UserStatus::Active : $request->previous_user_status);

            return $request;
        });
    }

    public function pendingFor(User $user): ?AccountDeletionRequest
    {
        return $this->requests->pendingFor($user);
    }
}
