<?php

namespace App\Enums\System;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;
use App\Enums\Settings\SettingKey;

/**
 * Every feature flag the platform knows. A flag with no stored row uses `defaultEnabled()`; for `wallet` and `offers` that is
 * the legacy `feature_*` setting (ADR-193), so existing installs keep their behaviour until a flag row is saved.
 */
enum FeatureFlagKey: string implements HasLabel
{
    use EnumHelpers;

    case Wallet = 'wallet';
    case Offers = 'offers';
    case Referrals = 'referrals';
    case Loyalty = 'loyalty';

    public function label(): string
    {
        return __("system.enums.flag.{$this->value}");
    }

    /**
     * The legacy setting that supplies the default, when there is one.
     */
    public function legacySetting(): ?SettingKey
    {
        return match ($this) {
            self::Wallet => SettingKey::FeatureWalletEnabled,
            self::Offers => SettingKey::FeatureOffersEnabled,
            default => null,
        };
    }
}
