<?php

namespace Database\Factories;

use App\Enums\Provider\DocumentStatus;
use App\Enums\Provider\FileScanStatus;
use App\Models\DocumentType;
use App\Models\ProviderDocument;
use App\Models\ProviderProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * A document row without a real file (tests that need the file store one through the upload endpoint).
 *
 * @extends Factory<ProviderDocument>
 */
class ProviderDocumentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'provider_profile_id' => ProviderProfile::factory(),
            'document_type_id' => DocumentType::factory(),
            'path' => 'providers/test/documents/'.fake()->uuid().'.jpg',
            'original_name' => 'scan',
            'mime' => 'image/jpeg',
            'size_bytes' => 1024,
            'file_hash' => hash('sha256', fake()->uuid()),
            'scan_status' => FileScanStatus::Skipped,
            'status' => DocumentStatus::Pending,
        ];
    }

    public function status(DocumentStatus $status): static
    {
        return $this->state(['status' => $status, 'reviewed_at' => $status === DocumentStatus::Pending ? null : now()]);
    }

    public function approved(?string $expires = null): static
    {
        return $this->state(['status' => DocumentStatus::Approved, 'reviewed_at' => now(), 'expires_at' => $expires]);
    }
}
