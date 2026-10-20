<?php

namespace App\Services\Customers;

use App\DTOs\Customers\CustomerDetails;
use App\Enums\Common\AuditAction;
use App\Exceptions\BusinessRuleViolation;
use App\Models\User;
use App\Repositories\Contracts\Identity\AccountDeletionRequestRepositoryInterface;
use App\Repositories\Contracts\Identity\CustomerProfileRepositoryInterface;
use App\Services\Audit\AuditLogger;
use App\Support\Auth\PhoneNumber;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Read side of the customer show page plus the audited reveal of contact details (SRS §13.3: minimise exposure of
 * phone numbers). Everything is masked until an admin holding `customers.view_pii` asks to reveal it.
 */
final class CustomerService
{
    public function __construct(
        private readonly CustomerProfileRepositoryInterface $profiles,
        private readonly AccountDeletionRequestRepositoryInterface $deletionRequests,
        private readonly AuditLogger $audit,
    ) {}

    public function details(User $customer, bool $canRevealContact): CustomerDetails
    {
        $profile = $customer->customerProfile;

        return new CustomerDetails(
            user: $customer,
            profile: $profile,
            pendingDeletion: $this->deletionRequests->pendingFor($customer),
            maskedPhone: $customer->phone_e164 === null ? '—' : PhoneNumber::mask($customer->phone_e164),
            maskedEmail: $customer->email === null ? null : $this->maskEmail($customer->email),
            maskedEmergencyPhone: $profile?->emergency_contact_phone === null ? null : PhoneNumber::mask($profile->emergency_contact_phone),
            canRevealContact: $canRevealContact,
        );
    }

    /**
     * The full contact details. Each call writes an audit entry (which fields, never the values).
     *
     * @return array{phone: string|null, email: string|null, emergency_contact_phone: string|null}
     */
    public function revealContact(User $customer, ?Authenticatable $actor): array
    {
        if ($customer->isAnonymised()) {
            throw BusinessRuleViolation::make('customer_anonymised');
        }

        $profile = $this->profiles->firstOrCreateFor($customer);
        $contact = [
            'phone' => $customer->phone_e164,
            'email' => $customer->email,
            'emergency_contact_phone' => $profile->emergency_contact_phone,
        ];

        $this->audit->record(
            AuditAction::PhoneRevealed,
            $customer,
            $actor,
            meta: ['fields' => array_keys(array_filter($contact, static fn (?string $value): bool => $value !== null))],
        );

        return $contact;
    }

    private function maskEmail(string $email): string
    {
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');

        return mb_substr($local, 0, 1).'•••@'.$domain;
    }
}
