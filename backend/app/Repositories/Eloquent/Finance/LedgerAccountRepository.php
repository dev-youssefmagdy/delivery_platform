<?php

namespace App\Repositories\Eloquent\Finance;

use App\DTOs\Finance\LedgerAccountFilterData;
use App\Enums\Finance\LedgerAccountType;
use App\Models\LedgerAccount;
use App\Models\User;
use App\Repositories\Contracts\Finance\LedgerAccountRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class LedgerAccountRepository implements LedgerAccountRepositoryInterface
{
    public function find(int $id): ?LedgerAccount
    {
        return LedgerAccount::query()->find($id);
    }

    public function findByUuid(string $uuid): ?LedgerAccount
    {
        return LedgerAccount::query()->where('uuid', $uuid)->first();
    }

    public function findByIdentity(LedgerAccountType $type, string $scopeKey, string $currency): ?LedgerAccount
    {
        return LedgerAccount::query()->where('type', $type->value)->where('scope_key', $scopeKey)->where('currency', $currency)->first();
    }

    public function firstOrCreateFor(LedgerAccountType $type, ?int $ownerId, string $currency): LedgerAccount
    {
        $scopeKey = LedgerAccount::scopeKeyFor($type, $ownerId);
        $existing = $this->findByIdentity($type, $scopeKey, $currency);

        if ($existing instanceof LedgerAccount) {
            return $existing;
        }

        // insertOrIgnore: a concurrent creator of the same identity wins on the unique index and we read its row.
        $now = now();
        DB::table('ledger_accounts')->insertOrIgnore([
            'uuid' => (string) Str::uuid(),
            'type' => $type->value,
            'owner_type' => $type->ownerType(),
            'owner_id' => $type->isOwned() ? $ownerId : null,
            'scope_key' => $scopeKey,
            'currency' => $currency,
            'balance_minor' => 0,
            'allow_negative' => $type->allowsNegative(),
            'credit_limit_minor' => null,
            'lock_version' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $this->findByIdentity($type, $scopeKey, $currency) ?? throw new \LogicException('Ledger account could not be created.');
    }

    public function lockInOrder(array $ids): Collection
    {
        $ids = array_values(array_unique($ids));
        sort($ids);

        return LedgerAccount::query()->whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
    }

    public function writeBalance(LedgerAccount $locked, int $balanceMinor): void
    {
        DB::table('ledger_accounts')->where('id', $locked->id)->update([
            'balance_minor' => $balanceMinor,
            'lock_version' => DB::raw('lock_version + 1'),
            'updated_at' => now(),
        ]);

        $locked->forceFill(['balance_minor' => $balanceMinor, 'lock_version' => $locked->lock_version + 1])->syncOriginal();
    }

    public function forOwner(LedgerAccountType $type, int $ownerId): Collection
    {
        return LedgerAccount::query()->where('type', $type->value)->where('owner_id', $ownerId)->orderBy('id')->get();
    }

    public function datatableQuery(LedgerAccountFilterData $filters): Builder
    {
        return LedgerAccount::query()
            ->with(['owner' => static fn (MorphTo $morph) => $morph->constrain([
                User::class => static fn ($query) => $query->withTrashed(),
            ])])
            ->when($filters->types !== [], static fn (Builder $query) => $query->whereIn('type', array_map(static fn (LedgerAccountType $type): string => $type->value, $filters->types)))
            ->when($filters->currency !== null, static fn (Builder $query) => $query->where('currency', $filters->currency))
            ->when($filters->negativeOnly === true, static fn (Builder $query) => $query->where('balance_minor', '<', 0))
            ->when($filters->balanceMinMinor !== null, static fn (Builder $query) => $query->where('balance_minor', '>=', $filters->balanceMinMinor))
            ->when($filters->balanceMaxMinor !== null, static fn (Builder $query) => $query->where('balance_minor', '<=', $filters->balanceMaxMinor))
            ->when($filters->uuid !== null, static fn (Builder $query) => $query->where('uuid', $filters->uuid));
    }

    public function allForReconciliation(): Collection
    {
        return LedgerAccount::query()->orderBy('id')->get(['id', 'uuid', 'type', 'currency', 'balance_minor']);
    }

    public function walletLookup(string $term, int $limit): Collection
    {
        $like = '%'.addcslashes($term, '%_\\').'%';
        $prefix = addcslashes($term, '%_\\').'%';

        return LedgerAccount::query()
            ->whereIn('type', [LedgerAccountType::CustomerWallet->value, LedgerAccountType::ProviderWallet->value])
            ->when($term !== '', static fn (Builder $query) => $query->where(static function (Builder $inner) use ($term, $like, $prefix): void {
                $inner->where('uuid', $term)
                    ->orWhere(static fn (Builder $customers) => $customers->where('type', LedgerAccountType::CustomerWallet->value)
                        ->whereIn('owner_id', static fn ($sub) => $sub->select('id')->from('users')->where('name', 'like', $like)->orWhere('phone_e164', 'like', $prefix)))
                    ->orWhere(static fn (Builder $providers) => $providers->where('type', LedgerAccountType::ProviderWallet->value)
                        ->whereIn('owner_id', static fn ($sub) => $sub->select('provider_profiles.id')->from('provider_profiles')
                            ->join('users', 'users.id', '=', 'provider_profiles.user_id')
                            ->where(static fn ($match) => $match->where('users.name', 'like', $like)->orWhere('users.phone_e164', 'like', $prefix))));
            }))
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    public function walletsByIds(array $ids): Collection
    {
        return LedgerAccount::query()
            ->whereIn('type', [LedgerAccountType::CustomerWallet->value, LedgerAccountType::ProviderWallet->value])
            ->whereKey($ids)
            ->get();
    }
}
