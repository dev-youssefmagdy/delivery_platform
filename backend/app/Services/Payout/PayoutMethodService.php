<?php

namespace App\Services\Payout;

use App\DTOs\Payout\PayoutMethodData;
use App\Enums\Common\AuditAction;
use App\Exceptions\BusinessRuleViolation;
use App\Exceptions\ConflictException;
use App\Models\Admin;
use App\Models\ProviderPayoutMethod;
use App\Models\ProviderProfile;
use App\Repositories\Contracts\Payout\PayoutRequestRepositoryInterface;
use App\Repositories\Contracts\Payout\ProviderPayoutMethodRepositoryInterface;
use App\Services\Audit\AuditLogger;
use Carbon\CarbonImmutable;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;

/**
 * Payout methods (SRS §7.6). The provider enrols a method; its details are encrypted at rest and only the type, label and last
 * digits ever leave the server. A method is usable for a payout only after finance verified it, and changing the details drops the
 * verification. A method with an open payout cannot be removed.
 */
final class PayoutMethodService
{
    public function __construct(
        private readonly ProviderPayoutMethodRepositoryInterface $methods,
        private readonly PayoutRequestRepositoryInterface $payouts,
        private readonly AuditLogger $audit,
        private readonly DatabaseManager $db,
    ) {}

    /**
     * @return Collection<int, ProviderPayoutMethod>
     */
    public function list(ProviderProfile $provider): Collection
    {
        return $this->methods->forProvider($provider->id);
    }

    public function create(ProviderProfile $provider, PayoutMethodData $data): ProviderPayoutMethod
    {
        $details = $this->cleanDetails($data);

        return $this->db->transaction(function () use ($provider, $data, $details): ProviderPayoutMethod {
            $existing = $this->methods->lockForProvider($provider->id);
            $default = $data->makeDefault || $existing->isEmpty();

            if ($default) {
                $this->methods->clearDefault($provider->id);
            }

            $method = $this->methods->insert([
                'provider_profile_id' => $provider->id,
                'type' => $data->type,
                'label' => $data->label,
                'details' => $details,
                'last4' => $this->last4($details[$data->type->identifierField()]),
                'is_default' => $default,
            ]);

            $this->audit->record(AuditAction::Created, $method, $provider->user, after: $method->masked() + ['is_default' => $default]);

            return $method;
        });
    }

    public function update(ProviderProfile $provider, string $uuid, PayoutMethodData $data): ProviderPayoutMethod
    {
        $details = $this->cleanDetails($data);

        return $this->db->transaction(function () use ($provider, $uuid, $data, $details): ProviderPayoutMethod {
            $this->methods->lockForProvider($provider->id);
            $method = $this->methods->findForProvider($provider->id, $uuid) ?? throw (new ModelNotFoundException)->setModel(ProviderPayoutMethod::class);

            if ($this->openPayoutsUse($method)) {
                throw ConflictException::make('payout_method_in_use');
            }

            if ($data->makeDefault) {
                $this->methods->clearDefault($provider->id);
            }

            // New details are a new destination: finance verifies it again.
            $changed = $method->type !== $data->type || $method->details !== $details;
            $before = $method->masked();
            $this->methods->update($method, [
                'type' => $data->type,
                'label' => $data->label,
                'details' => $details,
                'last4' => $this->last4($details[$data->type->identifierField()]),
                'is_default' => $data->makeDefault || $method->is_default,
                ...($changed ? ['verified_at' => null, 'verified_by_id' => null, 'verification_note' => null] : []),
            ]);

            $this->audit->record(AuditAction::Updated, $method, $provider->user, before: $before, after: $method->masked(), meta: ['reverification_required' => $changed]);

            return $method;
        });
    }

    public function makeDefault(ProviderProfile $provider, string $uuid): ProviderPayoutMethod
    {
        return $this->db->transaction(function () use ($provider, $uuid): ProviderPayoutMethod {
            $this->methods->lockForProvider($provider->id);
            $method = $this->methods->findForProvider($provider->id, $uuid) ?? throw (new ModelNotFoundException)->setModel(ProviderPayoutMethod::class);
            $this->methods->clearDefault($provider->id);

            return $this->methods->update($method, ['is_default' => true]);
        });
    }

    public function delete(ProviderProfile $provider, string $uuid): void
    {
        $this->db->transaction(function () use ($provider, $uuid): void {
            $this->methods->lockForProvider($provider->id);
            $method = $this->methods->findForProvider($provider->id, $uuid) ?? throw (new ModelNotFoundException)->setModel(ProviderPayoutMethod::class);

            if ($this->openPayoutsUse($method)) {
                throw ConflictException::make('payout_method_in_use');
            }

            $this->methods->delete($method);
            $this->audit->record(AuditAction::Deleted, $method, $provider->user, before: $method->masked());
        });
    }

    /**
     * Finance confirms the destination belongs to the provider (admin action; the policy checks `payouts.review`).
     */
    public function verify(Admin $actor, ProviderPayoutMethod $method, string $note): ProviderPayoutMethod
    {
        return $this->db->transaction(function () use ($actor, $method, $note): ProviderPayoutMethod {
            $locked = ProviderPayoutMethod::query()->whereKey($method->id)->lockForUpdate()->first() ?? throw (new ModelNotFoundException)->setModel(ProviderPayoutMethod::class);

            if ($locked->isVerified()) {
                throw ConflictException::make('payout_method_already_verified');
            }

            $this->methods->update($locked, ['verified_at' => CarbonImmutable::now(), 'verified_by_id' => $actor->id, 'verification_note' => $note]);
            $this->audit->record(AuditAction::Approved, $locked, $actor, after: $locked->masked() + ['verified' => true], reason: $note);

            return $locked;
        });
    }

    public function unverify(Admin $actor, ProviderPayoutMethod $method, string $reason): ProviderPayoutMethod
    {
        return $this->db->transaction(function () use ($actor, $method, $reason): ProviderPayoutMethod {
            $this->methods->update($method, ['verified_at' => null, 'verified_by_id' => null, 'verification_note' => null]);
            $this->audit->record(AuditAction::Rejected, $method, $actor, after: $method->masked() + ['verified' => false], reason: $reason);

            return $method;
        });
    }

    /**
     * @return array<string, string>
     */
    private function cleanDetails(PayoutMethodData $data): array
    {
        $details = [];

        foreach ($data->type->requiredFields() as $field) {
            $value = trim((string) ($data->details[$field] ?? ''));

            if ($value === '') {
                throw BusinessRuleViolation::make('payout_method_invalid_details', $field);
            }

            $details[$field] = $value;
        }

        foreach (['iban', 'branch'] as $optional) {
            if (isset($data->details[$optional]) && trim($data->details[$optional]) !== '') {
                $details[$optional] = trim($data->details[$optional]);
            }
        }

        return $details;
    }

    private function last4(string $identifier): string
    {
        $digits = preg_replace('/\s+/', '', $identifier) ?? $identifier;

        return mb_substr($digits, -4);
    }

    private function openPayoutsUse(ProviderPayoutMethod $method): bool
    {
        return $this->payouts->openCountForMethod($method->id) > 0;
    }
}
