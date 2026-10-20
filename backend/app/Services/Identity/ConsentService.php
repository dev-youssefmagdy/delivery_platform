<?php

namespace App\Services\Identity;

use App\DTOs\Identity\AcceptConsentData;
use App\DTOs\Identity\ConsentStatus;
use App\Enums\Identity\ClientApp;
use App\Exceptions\BusinessRuleViolation;
use App\Models\PolicyVersion;
use App\Models\User;
use App\Repositories\Contracts\Identity\CustomerProfileRepositoryInterface;
use App\Repositories\Contracts\Identity\UserConsentRepositoryInterface;
use App\Repositories\Contracts\Policy\PolicyVersionRepositoryInterface;
use Illuminate\Support\Facades\DB;

/**
 * Terms/privacy acceptance (SRS §6.2 step 7, §13.3). A consent always references the exact `policy_versions` row
 * the user saw; only the current published version of a type can be accepted.
 */
final class ConsentService
{
    public function __construct(
        private readonly PolicyVersionRepositoryInterface $policies,
        private readonly UserConsentRepositoryInterface $consents,
        private readonly CustomerProfileRepositoryInterface $customerProfiles,
    ) {}

    public function status(User $user, ClientApp $app): ConsentStatus
    {
        $currentByType = $this->policies->currentPublished()->keyBy(static fn (PolicyVersion $policy): string => $policy->type->value);

        $required = [];

        foreach ($app->requiredPolicyTypes() as $type) {
            if ($currentByType->has($type->value)) {
                $required[] = $currentByType->get($type->value);
            }
        }

        $acceptedIds = $this->consents->acceptedPolicyIds($user);
        $pending = array_values(array_filter(
            $required,
            static fn (PolicyVersion $policy): bool => ! in_array($policy->id, $acceptedIds, true),
        ));

        return new ConsentStatus($required, $pending, $this->consents->forUser($user));
    }

    /**
     * @throws BusinessRuleViolation `policy_version_not_current` for an unknown, draft or superseded version
     */
    public function accept(User $user, ClientApp $app, AcceptConsentData $data): ConsentStatus
    {
        $uuids = array_values(array_unique($data->policyUuids));
        $current = $this->policies->currentPublishedByUuids($uuids);

        if ($current->count() !== count($uuids)) {
            throw BusinessRuleViolation::make('policy_version_not_current', 'policies');
        }

        DB::transaction(function () use ($user, $app, $data, $current): void {
            foreach ($current as $policy) {
                $this->consents->createIfMissing($user, $policy->id, [
                    'locale' => $data->locale,
                    'app' => $app->value,
                    'ip' => $data->ip,
                    'accepted_at' => now(),
                ]);
            }

            if ($app === ClientApp::Customer && $data->marketingOptIn !== null) {
                $this->setMarketingOptIn($user, $data->marketingOptIn);
            }
        });

        return $this->status($user, $app);
    }

    private function setMarketingOptIn(User $user, bool $optIn): void
    {
        $profile = $this->customerProfiles->firstOrCreateFor($user);

        if ($profile->marketing_opt_in === $optIn) {
            return;
        }

        $this->customerProfiles->update($profile, [
            'marketing_opt_in' => $optIn,
            'marketing_opt_in_at' => $optIn ? now() : null,
        ]);
    }
}
