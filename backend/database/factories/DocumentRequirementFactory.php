<?php

namespace Database\Factories;

use App\Models\DocumentRequirement;
use App\Models\DocumentType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Creates a mandatory requirement with no scope (applies to everyone).
 *
 * @extends Factory<DocumentRequirement>
 */
class DocumentRequirementFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return ['document_type_id' => DocumentType::factory(), 'mandatory' => true];
    }

    public function optional(): static
    {
        return $this->state(['mandatory' => false]);
    }
}
