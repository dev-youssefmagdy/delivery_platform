<?php

namespace App\Services\Safety;

use App\DTOs\Safety\LocationFix;
use App\Enums\Safety\TrustedContactActionStatus;
use App\Enums\Safety\TrustedContactActionType;
use App\Integrations\Sms\Contracts\SmsGateway;
use App\Integrations\Sms\Exceptions\SmsDeliveryException;
use App\Models\Booking;
use App\Models\SafetyIncident;
use App\Models\TrustedContact;
use App\Models\User;
use App\Repositories\Contracts\Safety\TrustedContactRepositoryInterface;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Texts trusted contacts, only as far as each contact's consent flags allow, and writes one action record per contact with what
 * was and was not sent (never the phone in clear). A failing gateway is recorded as failed, never reported as sent.
 */
final class TrustedContactNotifier
{
    public function __construct(
        private readonly TrustedContactRepositoryInterface $contacts,
        private readonly Container $container,
    ) {}

    /**
     * @return array{total: int, sent: int, failed: int, skipped: int}
     */
    public function sosAlert(User $user, SafetyIncident $incident, ?Booking $booking, ?LocationFix $location): array
    {
        $result = ['total' => 0, 'sent' => 0, 'failed' => 0, 'skipped' => 0];

        foreach ($this->contacts->forUser($user->id) as $contact) {
            /** @var TrustedContact $contact */
            $result['total']++;

            if (! $contact->notify_on_sos) {
                $this->record($user, $contact, TrustedContactActionType::SosAlert, TrustedContactActionStatus::Skipped, $incident->id, $booking?->id, 'sos_not_consented');
                $result['skipped']++;

                continue;
            }

            $withLocation = $contact->share_location_on_sos && $location !== null;
            $text = $this->localized($user, fn (): string => (string) __('safety.sms.sos_alert', [
                'name' => $this->firstName($user),
                'time' => $incident->occurred_at->copy()->setTimezone((string) config('admin.display_timezone', 'UTC'))->format('H:i'),
            ]).($withLocation ? ' '.__('safety.sms.position', ['link' => $this->mapLink($location)]) : ''));

            $status = $this->send($contact, $text, $gateway);
            $this->record($user, $contact, TrustedContactActionType::SosAlert, $status, $incident->id, $booking?->id, $status === TrustedContactActionStatus::Sent ? null : 'gateway_failed', $withLocation && $status === TrustedContactActionStatus::Sent, false, $gateway);
            $result[$status === TrustedContactActionStatus::Sent ? 'sent' : 'failed']++;
        }

        return $result;
    }

    /**
     * @return array{total: int, sent: int, failed: int, skipped: int}
     */
    public function tripShare(User $user, Booking $booking, string $url, CarbonInterface $expiresAt): array
    {
        $result = ['total' => 0, 'sent' => 0, 'failed' => 0, 'skipped' => 0];
        $minutes = max(1, (int) now()->diffInMinutes($expiresAt, false));

        foreach ($this->contacts->forUser($user->id) as $contact) {
            /** @var TrustedContact $contact */
            $result['total']++;

            if (! $contact->share_trip) {
                $this->record($user, $contact, TrustedContactActionType::TripShare, TrustedContactActionStatus::Skipped, null, $booking->id, 'share_not_consented');
                $result['skipped']++;

                continue;
            }

            $text = $this->localized($user, fn (): string => (string) __('safety.sms.trip_share', ['name' => $this->firstName($user), 'url' => $url, 'minutes' => $minutes]));
            $status = $this->send($contact, $text, $gateway);
            $this->record($user, $contact, TrustedContactActionType::TripShare, $status, null, $booking->id, $status === TrustedContactActionStatus::Sent ? null : 'gateway_failed', false, true, $gateway, $expiresAt);
            $result[$status === TrustedContactActionStatus::Sent ? 'sent' : 'failed']++;
        }

        return $result;
    }

    private function send(TrustedContact $contact, string $text, ?string &$driver = null): TrustedContactActionStatus
    {
        try {
            $gateway = $this->container->make(SmsGateway::class);
            $driver = $gateway->name();
            $gateway->send($contact->phone(), $text);

            return TrustedContactActionStatus::Sent;
        } catch (SmsDeliveryException $e) {
            Log::warning('safety.trusted_contact_sms_failed', ['contact' => $contact->uuid, 'reason' => $e->getMessage()]);
        } catch (RuntimeException $e) {
            Log::error('safety.trusted_contact_gateway_unavailable', ['reason' => class_basename($e)]);
        }

        return TrustedContactActionStatus::Failed;
    }

    private function record(User $user, TrustedContact $contact, TrustedContactActionType $type, TrustedContactActionStatus $status, ?int $incidentId, ?int $bookingId, ?string $reason, bool $location = false, bool $link = false, ?string $gateway = null, ?CarbonInterface $expires = null): void
    {
        $this->contacts->recordAction([
            'user_id' => $user->id,
            'trusted_contact_id' => $contact->id,
            'incident_id' => $incidentId,
            'booking_id' => $bookingId,
            'action' => $type,
            'status' => $status,
            'reason' => $reason,
            'contact_name' => $contact->name,
            'contact_phone_masked' => $contact->maskedPhone(),
            'included_location' => $location,
            'included_trip_link' => $link,
            'gateway' => $gateway,
            'trip_link_expires_at' => $expires,
        ]);
    }

    private function localized(User $user, callable $text): string
    {
        $previous = App::getLocale();
        App::setLocale(in_array($user->preferred_locale, ['ar', 'en'], true) ? (string) $user->preferred_locale : 'ar');

        try {
            return $text();
        } finally {
            App::setLocale($previous);
        }
    }

    private function firstName(User $user): string
    {
        return (string) (preg_split('/\s+/u', trim((string) $user->name))[0] ?? '') ?: (string) __('safety.sms.someone');
    }

    private function mapLink(LocationFix $fix): string
    {
        return sprintf('https://www.openstreetmap.org/?mlat=%1$.5f&mlon=%2$.5f#map=17/%1$.5f/%2$.5f', $fix->lat, $fix->lng);
    }
}
