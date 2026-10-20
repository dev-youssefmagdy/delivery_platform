<?php

namespace Database\Factories;

use App\Enums\Provider\DocumentSubject;
use App\Models\DocumentType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentType>
 */
class DocumentTypeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'doc_'.fake()->unique()->bothify('????##'),
            'name' => ['ar' => 'مستند '.fake()->unique()->numerify('####'), 'en' => 'Document '.fake()->unique()->numerify('####')],
            'description' => ['ar' => 'وصف', 'en' => 'Description'],
            'applies_to' => DocumentSubject::Provider,
            'requires_expiry' => false,
            'requires_number' => false,
            'allowed_mimes' => ['image/jpeg', 'image/png', 'application/pdf'],
            'max_size_kb' => 5120,
            'sort_order' => fake()->numberBetween(0, 100),
            'active' => true,
        ];
    }

    public function forVehicle(): static
    {
        return $this->state(['applies_to' => DocumentSubject::Vehicle]);
    }

    public function expiring(): static
    {
        return $this->state(['requires_expiry' => true]);
    }

    public function inactive(): static
    {
        return $this->state(['active' => false]);
    }
}
