<?php

namespace App\Services\Courier;

use App\Enums\Booking\ContactRole;
use App\Enums\Settings\SettingKey;
use App\Models\Booking;
use App\Models\BookingContact;
use App\Repositories\Contracts\Booking\BookingContactRepositoryInterface;
use App\Services\Settings\SettingsService;
use App\Support\Auth\PhoneNumber;

/**
 * Parcel contacts (SRS §5.1-C): the sender and the recipient arrive as contact fields of the service form, are pulled out
 * of `service_details` before it is stored (so the clear-text phone never lives in the JSON column) and kept encrypted in
 * `booking_contacts`.
 *
 * Disclosure (Plan I10): a provider sees a contact only after selection, and its phone only while the job is in flight; the
 * customer's own app shows what the customer typed with the phone masked. Before selection a provider only gets the
 * request card, which has no contact data at all.
 */
final class BookingContactService
{
    public function __construct(
        private readonly BookingContactRepositoryInterface $contacts,
        private readonly SettingsService $settings,
    ) {}

    /**
     * Splits the validated `service_details` into the details to store and the contacts to keep encrypted.
     *
     * @param array<string, mixed> $details
     * @return array{0: array<string, mixed>, 1: array<string, array{name: string, phone: string}>}
     */
    public function split(array $details): array
    {
        $contacts = [];

        foreach (ContactRole::cases() as $role) {
            $value = $details[$role->fieldKey()] ?? null;

            if (! is_array($value)) {
                continue;
            }

            unset($details[$role->fieldKey()]);
            $phone = PhoneNumber::normalize(is_string($value['phone'] ?? null) ? $value['phone'] : null);
            $name = trim((string) ($value['name'] ?? ''));

            if ($phone !== null && $name !== '') {
                $contacts[$role->value] = ['name' => $name, 'phone' => $phone];
            }
        }

        return [$details, $contacts];
    }

    /**
     * @param array<string, array{name: string, phone: string}> $contacts
     */
    public function store(Booking $booking, array $contacts): void
    {
        $this->contacts->replace($booking, $contacts);
    }

    /**
     * The stored contacts as service-form answers, for validating an edited request as a whole.
     *
     * @return array<string, array{name: string, phone: string}>
     */
    public function asFormAnswers(Booking $booking): array
    {
        $answers = [];

        foreach ($this->contacts->forBooking($booking->id) as $contact) {
            if ($contact->name() !== null && $contact->phone() !== null) {
                $answers[$contact->role->fieldKey()] = ['name' => (string) $contact->name(), 'phone' => (string) $contact->phone()];
            }
        }

        return $answers;
    }

    public function recipientPhone(Booking $booking): ?string
    {
        return $this->contacts->find($booking->id, ContactRole::Recipient)?->phone();
    }

    /**
     * What the assigned provider may see. Name always (to hand the parcel over); the phone only while the job is in flight,
     * and as the real number or masked per the platform's phone-exposure setting.
     *
     * @return list<array<string, mixed>>
     */
    public function forProvider(Booking $booking): array
    {
        if (! $booking->status->isAssigned()) {
            return $this->view($booking, exposePhone: false, direct: false);
        }

        return $this->view($booking, exposePhone: true, direct: $this->settings->bool(SettingKey::DirectPhoneExposure));
    }

    /**
     * What the booking's customer sees of what they typed: the name and a masked phone.
     *
     * @return list<array<string, mixed>>
     */
    public function forCustomer(Booking $booking): array
    {
        return $this->view($booking, exposePhone: false, direct: false);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function view(Booking $booking, bool $exposePhone, bool $direct): array
    {
        return $this->contacts->forBooking($booking->id)->map(static function (BookingContact $contact) use ($exposePhone, $direct): array {
            $phone = $contact->phone();

            return [
                'role' => $contact->role->value,
                'name' => $contact->name(),
                'phone' => $exposePhone && $direct ? $phone : null,
                'phone_masked' => $phone === null ? null : PhoneNumber::mask($phone),
                'contact_mode' => $exposePhone && $direct ? 'direct' : 'masked',
            ];
        })->values()->all();
    }
}
