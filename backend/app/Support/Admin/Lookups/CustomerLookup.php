<?php

namespace App\Support\Admin\Lookups;

use App\DTOs\Admin\LookupOptionData;
use App\Enums\Admin\AdminPermission;
use App\Models\User;
use App\Repositories\Contracts\Customers\CustomerRepositoryInterface;
use App\Support\Auth\PhoneNumber;

/**
 * Customers for remote selects (requires customers.view). Options carry the name and the masked phone only.
 */
final class CustomerLookup implements Lookup
{
    public function __construct(private readonly CustomerRepositoryInterface $customers) {}

    public function permission(): AdminPermission
    {
        return AdminPermission::CustomersView;
    }

    public function search(string $term, int $limit): array
    {
        return $this->map($this->customers->lookup($term, $limit)->all());
    }

    public function resolve(array $ids): array
    {
        return $this->map($this->customers->findManyByIds($ids)->all());
    }

    /**
     * @param array<int, User> $items
     * @return list<LookupOptionData>
     */
    private function map(array $items): array
    {
        return array_values(array_map(static function (User $customer): LookupOptionData {
            $label = ($customer->name ?? __('admin.customers.unnamed')).($customer->phone_e164 === null ? '' : ' · '.PhoneNumber::mask($customer->phone_e164));

            return new LookupOptionData($customer->id, $label, ['uuid' => $customer->uuid]);
        }, $items));
    }
}
