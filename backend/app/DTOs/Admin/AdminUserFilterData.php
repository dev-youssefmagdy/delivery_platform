<?php

namespace App\DTOs\Admin;

use App\DTOs\Concerns\ParsesFilterInput;
use App\DTOs\Contracts\FilterData;
use App\Enums\Admin\AdminStatus;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/**
 * Filters of the admin users listing (Plan Appendix C: name/email, role, status, MFA enabled, created range).
 */
final readonly class AdminUserFilterData implements FilterData
{
    use ParsesFilterInput;

    /**
     * @param list<AdminStatus> $statuses
     */
    public function __construct(
        public ?string $search = null,
        public ?string $role = null,
        public array $statuses = [],
        public ?bool $mfaEnabled = null,
        public ?CarbonImmutable $createdFrom = null,
        public ?CarbonImmutable $createdTo = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        $input = self::filterInput($request);
        [$createdFrom, $createdTo] = self::dateRangeFilter($input, 'created_at');

        return new self(
            search: self::stringFilter($input, 'search'),
            role: self::stringFilter($input, 'role', 64),
            statuses: self::enumListFilter($input, 'statuses', AdminStatus::class),
            mfaEnabled: self::boolFilter($input, 'mfa_enabled'),
            createdFrom: $createdFrom,
            createdTo: $createdTo,
        );
    }

    public function activeCount(): int
    {
        return self::countActive([$this->search, $this->role, $this->statuses, $this->mfaEnabled, $this->createdFrom ?? $this->createdTo]);
    }

    public function toArray(): array
    {
        return array_filter([
            'search' => $this->search,
            'role' => $this->role,
            'statuses' => array_map(static fn (AdminStatus $status): string => $status->value, $this->statuses),
            'mfa_enabled' => $this->mfaEnabled === null ? null : (int) $this->mfaEnabled,
            'created_at' => array_filter(['from' => $this->createdFrom?->toDateString(), 'to' => $this->createdTo?->toDateString()]),
        ], static fn (mixed $value): bool => $value !== null && $value !== []);
    }
}
