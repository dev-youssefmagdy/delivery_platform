<?php

namespace App\Enums\Risk;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

enum RiskSignalType: string implements HasLabel
{
    use EnumHelpers;

    case CancellationRate = 'cancellation_rate';
    case NoShow = 'no_show';
    case OfferSpam = 'offer_spam';
    case LocationAnomaly = 'location_anomaly';
    case ReferralAbuse = 'referral_abuse';
    case AccountFarm = 'account_farm';
    case PaymentDispute = 'payment_dispute';

    public function label(): string
    {
        return __("reputation.enums.type.{$this->value}");
    }
}
