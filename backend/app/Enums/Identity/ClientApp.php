<?php

namespace App\Enums\Identity;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;
use App\Enums\Policy\PolicyType;
use Laravel\Sanctum\Contracts\HasAbilities;

/**
 * The mobile app a request comes from (header `X-App`). Doubles as the Sanctum token ability (ADR-05, ADR-62).
 */
enum ClientApp: string implements HasLabel
{
    use EnumHelpers;

    case Customer = 'customer';
    case Provider = 'provider';

    public const HEADER = 'X-App';

    /**
     * The app a Sanctum token was issued for (its single ability), or null for a token of neither app.
     */
    public static function fromToken(?HasAbilities $token): ?self
    {
        if ($token === null) {
            return null;
        }

        foreach (self::cases() as $app) {
            if ($token->can($app->ability())) {
                return $app;
            }
        }

        return null;
    }

    public function ability(): string
    {
        return $this->value;
    }

    /**
     * Policy types a user must have accepted (in their current version) to use this app. The driver terms are
     * accepted during provider onboarding (P2.1).
     *
     * @return list<PolicyType>
     */
    public function requiredPolicyTypes(): array
    {
        return [PolicyType::Terms, PolicyType::Privacy];
    }

    public function label(): string
    {
        return __("enums.client_app.{$this->value}");
    }
}
