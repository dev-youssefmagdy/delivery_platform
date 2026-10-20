<?php

namespace Database\Factories;

use App\Enums\Provider\DocumentStatus;
use App\Enums\Provider\FileScanStatus;
use App\Models\DocumentType;
use App\Models\Vehicle;
use App\Models\VehicleDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VehicleDocument>
 */
class VehicleDocumentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vehicle_id' => Vehicle::factory(),
            'document_type_id' => DocumentType::factory()->forVehicle(),
            'path' => 'providers/test/documents/'.fake()->uuid().'.jpg',
            'original_name' => 'scan',
            'mime' => 'image/jpeg',
            'size_bytes' => 1024,
            'file_hash' => hash('sha256', fake()->uuid()),
            'scan_status' => FileScanStatus::Skipped,
            'status' => DocumentStatus::Pending,
        ];
    }

    public function approved(?string $expires = null): static
    {
        return $this->state(['status' => DocumentStatus::Approved, 'reviewed_at' => now(), 'expires_at' => $expires]);
    }
}
