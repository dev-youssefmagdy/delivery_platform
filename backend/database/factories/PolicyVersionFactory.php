<?php

namespace Database\Factories;

use App\Enums\Content\ContentStatus;
use App\Enums\Policy\PolicyType;
use App\Models\PolicyVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PolicyVersion>
 */
class PolicyVersionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => PolicyType::Terms,
            'title' => ['ar' => 'الشروط والأحكام', 'en' => 'Terms and conditions'],
            'content' => ['ar' => fake('ar_SA')->paragraph(), 'en' => fake()->paragraph()],
            'status' => ContentStatus::Draft,
            'version' => null,
        ];
    }

    public function published(int $version = 1): static
    {
        return $this->state(['status' => ContentStatus::Published, 'version' => $version, 'published_at' => now()]);
    }

    public function archived(int $version = 1): static
    {
        return $this->state(['status' => ContentStatus::Archived, 'version' => $version, 'published_at' => now()->subDay()]);
    }
}
