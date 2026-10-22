<?php

namespace App\Enums\Notification;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;
use App\Enums\Identity\ClientApp;

/**
 * Whom a push campaign addresses. Only people who opted in to marketing and have a registered device are reached.
 */
enum CampaignAudience: string implements HasLabel
{
    use EnumHelpers;

    case Customers = 'customers';
    case Providers = 'providers';
    case Everyone = 'everyone';

    /**
     * @return list<ClientApp>
     */
    public function apps(): array
    {
        return match ($this) {
            self::Customers => [ClientApp::Customer],
            self::Providers => [ClientApp::Provider],
            self::Everyone => [ClientApp::Customer, ClientApp::Provider],
        };
    }

    public function label(): string
    {
        return __("enums.campaign_audience.{$this->value}");
    }
}
