<?php

namespace App\Repositories\Eloquent\Payments;

use App\Enums\Payments\PaymentGatewayName;
use App\Models\PaymentMethod;
use App\Repositories\Contracts\Payments\PaymentMethodRepositoryInterface;
use Illuminate\Support\Collection;

final class PaymentMethodRepository implements PaymentMethodRepositoryInterface
{
    public function activeForUser(int $userId): Collection
    {
        return PaymentMethod::query()->where('user_id', $userId)->where('status', PaymentMethod::STATUS_ACTIVE)->orderByDesc('is_default')->orderByDesc('id')->get();
    }

    public function findOwned(string $uuid, int $userId): ?PaymentMethod
    {
        return PaymentMethod::query()->where('uuid', $uuid)->where('user_id', $userId)->where('status', PaymentMethod::STATUS_ACTIVE)->first();
    }

    public function find(int $id): ?PaymentMethod
    {
        return PaymentMethod::query()->find($id);
    }

    public function findBySetupReference(PaymentGatewayName $gateway, string $reference): ?PaymentMethod
    {
        return PaymentMethod::query()->where('gateway', $gateway->value)->where('setup_reference', $reference)->first();
    }

    public function insert(array $attributes): PaymentMethod
    {
        return PaymentMethod::query()->create($attributes);
    }

    public function update(PaymentMethod $method, array $attributes): PaymentMethod
    {
        $method->forceFill($attributes)->save();

        return $method;
    }

    public function activeCount(int $userId): int
    {
        return PaymentMethod::query()->where('user_id', $userId)->where('status', PaymentMethod::STATUS_ACTIVE)->count();
    }

    public function clearDefault(int $userId): void
    {
        PaymentMethod::query()->where('user_id', $userId)->where('is_default', true)->update(['is_default' => false]);
    }

    public function delete(PaymentMethod $method): void
    {
        $method->delete();
    }

    public function allForUser(int $userId): Collection
    {
        return PaymentMethod::withTrashed()->where('user_id', $userId)->get();
    }

    public function forceDelete(PaymentMethod $method): void
    {
        $method->forceDelete();
    }
}
