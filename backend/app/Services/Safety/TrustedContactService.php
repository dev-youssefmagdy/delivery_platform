<?php

namespace App\Services\Safety;

use App\DTOs\Safety\TrustedContactData;
use App\Exceptions\BusinessRuleViolation;
use App\Models\TrustedContact;
use App\Models\User;
use App\Repositories\Contracts\Safety\TrustedContactRepositoryInterface;
use App\Support\Auth\PhoneNumber;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;

/**
 * A person's trusted contacts (SRS §13.1). A contact is added only after the person confirms the contact agreed to be messaged;
 * the consent flags decide exactly what the platform may send. The phone is stored encrypted.
 */
final class TrustedContactService
{
    public function __construct(
        private readonly TrustedContactRepositoryInterface $contacts,
        private readonly DatabaseManager $db,
    ) {}

    /**
     * @return Collection<int, TrustedContact>
     */
    public function list(User $user): Collection
    {
        return $this->contacts->forUser($user->id);
    }

    /**
     * @throws BusinessRuleViolation `trusted_contact_consent_required`, `trusted_contact_phone_invalid`, `trusted_contact_is_self`,
     *                               `trusted_contact_duplicate`, `trusted_contacts_limit_reached`
     */
    public function add(User $user, TrustedContactData $data): TrustedContact
    {
        $phone = $this->checkedPhone($user, $data);

        return $this->db->transaction(function () use ($user, $data, $phone): TrustedContact {
            if ($this->contacts->countOfUser($user->id) >= (int) config('safety.trusted_contacts.max_per_user', 5)) {
                throw BusinessRuleViolation::make('trusted_contacts_limit_reached', context: ['max' => (int) config('safety.trusted_contacts.max_per_user', 5)]);
            }

            if ($this->contacts->phoneExists($user->id, PhoneNumber::hash($phone))) {
                throw BusinessRuleViolation::make('trusted_contact_duplicate', 'phone');
            }

            /** @var TrustedContact $contact */
            $contact = $this->contacts->create($this->attributes($user, $data, $phone) + ['consent_confirmed_at' => now()]);

            return $contact;
        });
    }

    public function update(User $user, string $uuid, TrustedContactData $data): TrustedContact
    {
        $phone = $this->checkedPhone($user, $data);

        return $this->db->transaction(function () use ($user, $uuid, $data, $phone): TrustedContact {
            $contact = $this->contacts->findOfUser($uuid, $user->id) ?? throw (new ModelNotFoundException)->setModel(TrustedContact::class);

            if ($this->contacts->phoneExists($user->id, PhoneNumber::hash($phone), $contact->id)) {
                throw BusinessRuleViolation::make('trusted_contact_duplicate', 'phone');
            }

            $changedPhone = $contact->phone() !== $phone;
            /** @var TrustedContact $updated */
            $updated = $this->contacts->update($contact, $this->attributes($user, $data, $phone) + ($changedPhone ? ['consent_confirmed_at' => now()] : []));

            return $updated;
        });
    }

    public function remove(User $user, string $uuid): void
    {
        $contact = $this->contacts->findOfUser($uuid, $user->id) ?? throw (new ModelNotFoundException)->setModel(TrustedContact::class);
        $this->contacts->delete($contact);
    }

    private function checkedPhone(User $user, TrustedContactData $data): string
    {
        if (! $data->consentConfirmed) {
            throw BusinessRuleViolation::make('trusted_contact_consent_required', 'consent_confirmed');
        }

        $phone = PhoneNumber::normalize($data->phone) ?? throw BusinessRuleViolation::make('trusted_contact_phone_invalid', 'phone');

        if ($user->phone_e164 === $phone) {
            throw BusinessRuleViolation::make('trusted_contact_is_self', 'phone');
        }

        return $phone;
    }

    /**
     * @return array<string, mixed>
     */
    private function attributes(User $user, TrustedContactData $data, string $phone): array
    {
        return [
            'user_id' => $user->id,
            'name' => trim($data->name),
            'phone_encrypted' => encrypt($phone),
            'phone_hash' => PhoneNumber::hash($phone),
            'relationship' => $data->relationship,
            'notify_on_sos' => $data->notifyOnSos,
            'share_trip' => $data->shareTrip,
            'share_location_on_sos' => $data->shareLocationOnSos && $data->notifyOnSos,
        ];
    }
}
