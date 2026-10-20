<?php

namespace App\Services\Catalog;

use App\DTOs\Catalog\CategoryPolicyData;
use App\Enums\Settings\SettingKey;
use App\Models\ServiceCategory;
use App\Services\Settings\SettingsService;

/**
 * Resolves category-level policy fields that may be null to the platform settings (SettingKey defaults), so
 * dispatch and booking code reads one effective value (Plan P1.4 step 1).
 */
final class CategoryPolicyResolver
{
    public function __construct(private readonly SettingsService $settings) {}

    /**
     * The platform-wide values a category inherits when its own field is empty.
     */
    public function defaults(): CategoryPolicyData
    {
        return new CategoryPolicyData(
            offerTtlSeconds: $this->settings->int(SettingKey::OfferTtlSeconds),
            searchTtlSeconds: $this->settings->int(SettingKey::BookingSearchTtlSeconds),
            dispatchRadiusStepsKm: array_map('floatval', $this->settings->list(SettingKey::DispatchRadiusStepsKm)),
            dispatchBatchSize: $this->settings->int(SettingKey::DispatchBatchSize),
            pinRequired: $this->settings->bool(SettingKey::RidePinRequired),
        );
    }

    public function resolve(ServiceCategory $category): CategoryPolicyData
    {
        $steps = $category->dispatch_radius_steps_km;

        return new CategoryPolicyData(
            offerTtlSeconds: $category->offer_ttl_seconds ?? $this->settings->int(SettingKey::OfferTtlSeconds),
            searchTtlSeconds: $category->search_ttl_seconds ?? $this->settings->int(SettingKey::BookingSearchTtlSeconds),
            dispatchRadiusStepsKm: array_map('floatval', $steps === null || $steps === [] ? $this->settings->list(SettingKey::DispatchRadiusStepsKm) : $steps),
            dispatchBatchSize: $category->dispatch_batch_size ?? $this->settings->int(SettingKey::DispatchBatchSize),
            pinRequired: $category->pin_required ?? $this->settings->bool(SettingKey::RidePinRequired),
        );
    }
}
