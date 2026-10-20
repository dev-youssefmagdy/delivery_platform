<?php

namespace App\Services\Customers;

use App\Models\AccountDeletionRequest;
use App\Models\CustomerMessage;
use App\Models\SavedPlace;
use App\Models\User;
use App\Models\UserConsent;
use App\Models\UserDevice;
use App\Repositories\Contracts\Customers\CustomerMessageRepositoryInterface;
use App\Repositories\Contracts\Geography\SavedPlaceRepositoryInterface;
use App\Repositories\Contracts\Identity\AccountDeletionRequestRepositoryInterface;
use App\Repositories\Contracts\Identity\CustomerProfileRepositoryInterface;
use App\Repositories\Contracts\Identity\UserConsentRepositoryInterface;
use App\Repositories\Contracts\Identity\UserDeviceRepositoryInterface;

/**
 * Builds the machine-readable copy of everything the platform holds about one customer (data-subject access
 * request, SRS §16.2 / §13.3). Push tokens and secrets are never included. Modules that store customer data later
 * (bookings, payments, tickets, ratings) add their section here; until then `sections_pending` says so honestly.
 */
final class PersonalDataExporter
{
    public function __construct(
        private readonly CustomerProfileRepositoryInterface $profiles,
        private readonly UserDeviceRepositoryInterface $devices,
        private readonly UserConsentRepositoryInterface $consents,
        private readonly SavedPlaceRepositoryInterface $savedPlaces,
        private readonly AccountDeletionRequestRepositoryInterface $deletionRequests,
        private readonly CustomerMessageRepositoryInterface $messages,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(User $customer): array
    {
        $profile = $this->profiles->firstOrCreateFor($customer);

        return [
            'exported_at' => now()->toIso8601String(),
            'account' => [
                'uuid' => $customer->uuid,
                'name' => $customer->name,
                'phone' => $customer->phone_e164,
                'phone_verified_at' => $customer->phone_verified_at?->toIso8601String(),
                'email' => $customer->email,
                'preferred_locale' => $customer->preferred_locale,
                'status' => $customer->status->value,
                'registered_at' => $customer->created_at->toIso8601String(),
                'last_seen_at' => $customer->last_seen_at?->toIso8601String(),
            ],
            'profile' => [
                'accessibility_needs' => $profile->accessibility_needs,
                'emergency_contact_name' => $profile->emergency_contact_name,
                'emergency_contact_phone' => $profile->emergency_contact_phone,
                'marketing_opt_in' => $profile->marketing_opt_in,
                'marketing_opt_in_at' => $profile->marketing_opt_in_at?->toIso8601String(),
            ],
            'devices' => $this->devices->datatableQuery($customer)->get()->map(static fn (UserDevice $device): array => [
                'app' => $device->app->value,
                'platform' => $device->platform->value,
                'device_id' => $device->device_id,
                'app_version' => $device->app_version,
                'locale' => $device->locale,
                'last_active_at' => $device->last_active_at?->toIso8601String(),
            ])->all(),
            'consents' => array_map(static fn (UserConsent $consent): array => [
                'policy_type' => $consent->policyVersion->type->value,
                'policy_version' => $consent->policyVersion->version,
                'accepted_at' => $consent->accepted_at->toIso8601String(),
                'locale' => $consent->locale,
                'app' => $consent->app->value,
                'ip' => $consent->ip,
            ], $this->consents->forUser($customer)),
            'saved_places' => $this->savedPlaces->forUser($customer->id)->map(static fn (SavedPlace $place): array => [
                'label' => $place->label,
                'type' => $place->type->value,
                'lat' => $place->point->lat,
                'lng' => $place->point->lng,
                'address' => $place->address['line'] ?? null,
            ])->all(),
            'deletion_requests' => $this->deletionRequests->datatableQuery($customer)->get()->map(static fn (AccountDeletionRequest $request): array => [
                'status' => $request->status->value,
                'reason' => $request->reason,
                'requested_at' => $request->requested_at->toIso8601String(),
                'processed_at' => $request->processed_at?->toIso8601String(),
            ])->all(),
            'help_messages' => $this->messages->datatableQuery($customer)->get()->map(static fn (CustomerMessage $message): array => [
                'subject' => $message->subject,
                'body' => $message->body,
                'sent_at' => $message->created_at?->toIso8601String(),
                'read_at' => $message->read_at?->toIso8601String(),
            ])->all(),
            'sections_pending' => ['bookings', 'payments', 'coupon_usage', 'support_tickets', 'ratings'],
        ];
    }
}
