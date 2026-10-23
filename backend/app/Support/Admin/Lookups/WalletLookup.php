<?php

namespace App\Support\Admin\Lookups;

use App\DTOs\Admin\LookupOptionData;
use App\Enums\Admin\AdminPermission;
use App\Enums\Finance\LedgerAccountType;
use App\Models\LedgerAccount;
use App\Models\ProviderProfile;
use App\Models\User;
use App\Repositories\Contracts\Finance\LedgerAccountRepositoryInterface;
use App\Support\Auth\PhoneNumber;

/**
 * Customer and provider wallets for the adjustment form (requires wallets.adjust). The label carries the owner's name, the
 * masked phone, the wallet type and its currency.
 */
final class WalletLookup implements Lookup
{
    public function __construct(private readonly LedgerAccountRepositoryInterface $accounts) {}

    public function permission(): AdminPermission
    {
        return AdminPermission::WalletsAdjust;
    }

    public function search(string $term, int $limit): array
    {
        return $this->map($this->accounts->walletLookup($term, $limit)->all());
    }

    public function resolve(array $ids): array
    {
        return $this->map($this->accounts->walletsByIds(array_map('intval', $ids))->all());
    }

    /**
     * @param array<int, LedgerAccount> $accounts
     * @return list<LookupOptionData>
     */
    private function map(array $accounts): array
    {
        $collection = (new \Illuminate\Database\Eloquent\Collection($accounts))->loadMorph('owner', [ProviderProfile::class => ['user']]);

        return array_values(array_map(function (LedgerAccount $account): LookupOptionData {
            $owner = $account->owner;
            $user = $owner instanceof ProviderProfile ? $owner->user : ($owner instanceof User ? $owner : null);
            $phone = $user?->phone_e164;
            $name = $user->name ?? __('admin.ledger.accounts.unnamed');
            $label = $name.($phone === null ? '' : ' · '.PhoneNumber::mask($phone)).' · '.$account->type->label().' · '.$account->currency;

            return new LookupOptionData($account->id, $label, ['uuid' => $account->uuid, 'kind' => $account->type === LedgerAccountType::ProviderWallet ? 'provider' : 'customer']);
        }, $collection->all()));
    }
}
