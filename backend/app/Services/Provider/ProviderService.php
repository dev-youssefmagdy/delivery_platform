<?php

namespace App\Services\Provider;

use App\DTOs\Provider\ProviderDetails;
use App\Enums\Common\AuditAction;
use App\Enums\Identity\DeletionRequestStatus;
use App\Models\ProviderProfile;
use App\Repositories\Contracts\Identity\AccountDeletionRequestRepositoryInterface;
use App\Repositories\Contracts\Provider\VehicleRepositoryInterface;
use App\Services\Audit\AuditLogger;
use App\Support\Auth\PhoneNumber;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Read side of the provider show page and the audited reveal of the provider's contact details and birth date
 * (SRS §13.3: minimise exposure of personal data; `drivers.view_pii`).
 */
final class ProviderService
{
    public function __construct(
        private readonly ProviderEligibilityService $eligibility,
        private readonly VehicleRepositoryInterface $vehicles,
        private readonly AccountDeletionRequestRepositoryInterface $deletionRequests,
        private readonly AuditLogger $audit,
    ) {}

    public function details(ProviderProfile $profile, bool $canRevealContact): ProviderDetails
    {
        $user = $profile->user;
        $pending = $this->deletionRequests->pendingFor($user);

        return new ProviderDetails(
            profile: $profile,
            user: $user,
            canRevealContact: $canRevealContact && ! $user->isAnonymised(),
            eligibility: $this->eligibility->check($profile),
            vehicleCount: $this->vehicles->countForProvider($profile),
            pendingDeletionRequestUuid: $pending?->status === DeletionRequestStatus::Pending ? $pending->uuid : null,
        );
    }

    public function maskedPhone(ProviderProfile $profile): string
    {
        $phone = $profile->user->phone_e164;

        return $phone === null ? '—' : PhoneNumber::mask($phone);
    }

    /**
     * Phone, email and date of birth in clear text. Each call is audited (which fields, never the values).
     *
     * @return array{phone: string|null, email: string|null, date_of_birth: string|null}
     */
    public function revealContact(ProviderProfile $profile, ?Authenticatable $actor): array
    {
        $contact = [
            'phone' => $profile->user->phone_e164,
            'email' => $profile->user->email,
            'date_of_birth' => $profile->date_of_birth?->toDateString(),
        ];

        $this->audit->record(
            AuditAction::PhoneRevealed,
            $profile,
            $actor,
            meta: ['fields' => array_keys(array_filter($contact, static fn (?string $value): bool => $value !== null))],
        );

        return $contact;
    }
}
