<?php

namespace App\Repositories\Contracts\Payments;

use App\Enums\Payments\PaymentGatewayName;
use App\Models\PaymentMethod;
use Illuminate\Support\Collection;

interface PaymentMethodRepositoryInterface
{
    /**
     * Active methods of a customer, default first.
     *
     * @return Collection<int, PaymentMethod>
     */
    public function activeForUser(int $userId): Collection;

    public function findOwned(string $uuid, int $userId): ?PaymentMethod;

    public function find(int $id): ?PaymentMethod;

    public function findBySetupReference(PaymentGatewayName $gateway, string $reference): ?PaymentMethod;

    /**
     * @param array<string, mixed> $attributes
     */
    public function insert(array $attributes): PaymentMethod;

    /**
     * @param array<string, mixed> $attributes
     */
    public function update(PaymentMethod $method, array $attributes): PaymentMethod;

    public function activeCount(int $userId): int;

    public function clearDefault(int $userId): void;

    public function delete(PaymentMethod $method): void;

    /**
     * Every method row of a customer, pending ones and soft-deleted ones included (account anonymisation).
     *
     * @return Collection<int, PaymentMethod>
     */
    public function allForUser(int $userId): Collection;

    /**
     * Removes the row for good (the token is gone with it).
     */
    public function forceDelete(PaymentMethod $method): void;
}
