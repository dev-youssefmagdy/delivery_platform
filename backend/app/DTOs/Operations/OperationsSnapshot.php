<?php

namespace App\DTOs\Operations;

/**
 * The live map's data: online providers and active bookings, with nothing beyond ids, category and status. Contact
 * data, names and addresses never appear here.
 */
final readonly class OperationsSnapshot
{
    /**
     * @param list<array{id: string, lat: float, lng: float, category: string|null, busy: bool, age_s: int}> $providers
     * @param list<array{id: string, code: string, lat: float, lng: float, category: string|null, status: string, status_label: string}> $bookings
     */
    public function __construct(
        public array $providers,
        public array $bookings,
        public string $generatedAt,
        public bool $truncated,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'providers' => $this->providers,
            'bookings' => $this->bookings,
            'generated_at' => $this->generatedAt,
            'truncated' => $this->truncated,
        ];
    }
}
