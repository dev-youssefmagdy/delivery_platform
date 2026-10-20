<?php

namespace App\Services\Customers;

use App\DTOs\Customers\HelpMessageData;
use App\Enums\Common\AuditAction;
use App\Enums\Identity\UserStatus;
use App\Exceptions\BusinessRuleViolation;
use App\Models\Admin;
use App\Models\CustomerMessage;
use App\Models\User;
use App\Repositories\Contracts\Customers\CustomerMessageRepositoryInterface;
use App\Services\Audit\AuditLogger;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\DatabaseManager;

/**
 * "Send help message" (SRS §16.2): an in-app message from support. It appears in the customer's inbox
 * (`GET /me/messages`); push delivery is added by the notifications platform (P3.6).
 */
final class CustomerMessageService
{
    public function __construct(
        private readonly CustomerMessageRepositoryInterface $messages,
        private readonly AuditLogger $audit,
        private readonly DatabaseManager $db,
    ) {}

    public function send(User $customer, HelpMessageData $data, ?Authenticatable $actor): CustomerMessage
    {
        if ($customer->isAnonymised() || $customer->status === UserStatus::Deleted) {
            throw BusinessRuleViolation::make('customer_anonymised');
        }

        return $this->db->transaction(function () use ($customer, $data, $actor): CustomerMessage {
            /** @var CustomerMessage $message */
            $message = $this->messages->create([
                'user_id' => $customer->id,
                'sent_by_admin_id' => $actor instanceof Admin ? $actor->id : null,
                'subject' => $data->subject,
                'body' => $data->body,
            ]);

            $this->audit->record(AuditAction::MessageSent, $customer, $actor, after: ['message' => $message->uuid, 'subject' => $data->subject], reason: $data->reason);

            return $message;
        });
    }
}
