<?php

namespace App\Services\Risk;

use App\Enums\Risk\RiskSubjectType;
use App\Models\ProviderProfile;
use App\Models\RiskSignal;
use App\Models\User;
use App\Repositories\Contracts\Risk\RiskSignalRepositoryInterface;

/**
 * Who a signal is about, for the admin: display name and the link to the customer or provider page ("open provider / customer").
 * Resolved through the repository and memoised per request, never through lazy relations.
 */
final class RiskSubjectResolver
{
    /** @var array<string, array{name: string, url: string|null, model: User|ProviderProfile|null}> */
    private array $memo = [];

    public function __construct(private readonly RiskSignalRepositoryInterface $signals) {}

    public function customer(int $id): ?User
    {
        return $this->signals->customer($id);
    }

    public function provider(int $id): ?ProviderProfile
    {
        return $this->signals->provider($id);
    }

    /**
     * @return array{name: string, url: string|null, model: User|ProviderProfile|null}
     */
    public function describe(RiskSignal $signal): array
    {
        $key = $signal->subject_type->value.':'.$signal->subject_id;

        return $this->memo[$key] ??= $this->resolve($signal);
    }

    /**
     * @return array{name: string, url: string|null, model: User|ProviderProfile|null}
     */
    private function resolve(RiskSignal $signal): array
    {
        if ($signal->subject_type === RiskSubjectType::Provider) {
            $profile = $this->provider($signal->subject_id);

            return $profile === null
                ? ['name' => '—', 'url' => null, 'model' => null]
                : ['name' => (string) ($profile->user->name ?? __('reputation.admin.unnamed')), 'url' => route('admin.providers.show', $profile), 'model' => $profile];
        }

        $user = $this->customer($signal->subject_id);

        return $user === null
            ? ['name' => '—', 'url' => null, 'model' => null]
            : ['name' => (string) ($user->name ?? __('reputation.admin.unnamed')), 'url' => route('admin.customers.show', $user), 'model' => $user];
    }
}
