<?php

namespace App\Services\Customers;

use App\Models\CustomerMessage;
use App\Models\User;
use App\Repositories\Contracts\Customers\CustomerMessageRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * The customer's side of help messages: list the inbox and mark a message read.
 */
final class CustomerInboxService
{
    private const LIMIT = 50;

    public function __construct(private readonly CustomerMessageRepositoryInterface $messages) {}

    /**
     * @return Collection<int, CustomerMessage>
     */
    public function list(User $user): Collection
    {
        return $this->messages->latestForUser($user, self::LIMIT);
    }

    public function markRead(User $user, string $uuid): CustomerMessage
    {
        $message = $this->messages->findForUser($user, $uuid) ?? throw (new ModelNotFoundException)->setModel(CustomerMessage::class);

        if ($message->read_at === null) {
            $this->messages->update($message, ['read_at' => now()]);
        }

        return $message;
    }
}
