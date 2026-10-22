<?php

namespace App\DTOs\Payments;

use App\Enums\Common\ActorType;
use App\Models\Admin;
use App\Models\ProviderProfile;
use App\Models\User;

/**
 * Who or what moved a payment, and through which path (`webhook`, `query`, `system`, `admin`, `provider`, `api`). Written
 * on the payment's timeline.
 */
final readonly class PaymentActor
{
    public function __construct(public string $source, public ?ActorType $type = null, public ?int $id = null) {}

    public static function webhook(): self
    {
        return new self('webhook', ActorType::System);
    }

    public static function query(): self
    {
        return new self('query', ActorType::System);
    }

    public static function system(): self
    {
        return new self('system', ActorType::System);
    }

    public static function admin(Admin $admin): self
    {
        return new self('admin', ActorType::Admin, $admin->id);
    }

    public static function provider(ProviderProfile $provider): self
    {
        return new self('provider', ActorType::Provider, $provider->id);
    }

    public static function customer(User $customer): self
    {
        return new self('api', ActorType::Customer, $customer->id);
    }
}
