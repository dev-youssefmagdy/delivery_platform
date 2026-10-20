<?php

namespace Database\Factories;

use App\Enums\Provider\ProviderStatus;
use App\Enums\Provider\ProviderType;
use App\Models\City;
use App\Models\ProviderProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProviderProfile>
 */
class ProviderProfileFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'provider_type' => ProviderType::IndividualDriver,
            'status' => ProviderStatus::DocumentsPending,
            'city_id' => City::factory(),
            'date_of_birth' => now()->subYears(30)->toDateString(),
        ];
    }

    public function status(ProviderStatus $status): static
    {
        return $this->state(fn (): array => array_filter([
            'status' => $status,
            'status_changed_at' => now(),
            'submitted_at' => in_array($status, [ProviderStatus::UnderReview, ProviderStatus::MoreInformationRequired, ProviderStatus::Approved, ProviderStatus::Rejected], true) ? now()->subHours(2) : null,
            'approved_at' => $status === ProviderStatus::Approved ? now()->subDay() : null,
        ], static fn (mixed $value): bool => $value !== null));
    }

    public function approved(): static
    {
        return $this->status(ProviderStatus::Approved);
    }

    public function underReview(): static
    {
        return $this->status(ProviderStatus::UnderReview);
    }
}
