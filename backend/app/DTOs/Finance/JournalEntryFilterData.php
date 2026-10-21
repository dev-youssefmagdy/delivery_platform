<?php

namespace App\DTOs\Finance;

use App\DTOs\Concerns\ParsesFilterInput;
use App\DTOs\Contracts\FilterData;
use App\Enums\Finance\JournalEntryType;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/**
 * Filters of the Journal entries listing: type, reference, entry uuid or idempotency key, account, reversal flag and
 * the posted date range.
 */
final readonly class JournalEntryFilterData implements FilterData
{
    use ParsesFilterInput;

    /**
     * @param list<JournalEntryType> $types
     */
    public function __construct(
        public array $types = [],
        public ?string $reference = null,
        public ?string $search = null,
        public ?string $accountUuid = null,
        public ?bool $reversals = null,
        public ?CarbonImmutable $from = null,
        public ?CarbonImmutable $to = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        $input = self::filterInput($request);
        [$from, $to] = self::dateRangeFilter($input, 'posted_at');

        return new self(
            types: self::enumListFilter($input, 'types', JournalEntryType::class),
            reference: self::stringFilter($input, 'reference', 32),
            search: self::stringFilter($input, 'search', 120),
            accountUuid: self::stringFilter($input, 'account_uuid', 36),
            reversals: self::boolFilter($input, 'reversals'),
            from: $from,
            to: $to,
        );
    }

    public function activeCount(): int
    {
        return self::countActive([$this->types, $this->reference, $this->search, $this->accountUuid, $this->reversals, $this->from ?? $this->to]);
    }

    public function toArray(): array
    {
        return array_filter([
            'types' => array_map(static fn (JournalEntryType $type): string => $type->value, $this->types),
            'reference' => $this->reference,
            'search' => $this->search,
            'account_uuid' => $this->accountUuid,
            'reversals' => $this->reversals === null ? null : ($this->reversals ? '1' : '0'),
            'posted_at' => array_filter(['from' => $this->from?->toDateString(), 'to' => $this->to?->toDateString()]),
        ], static fn (mixed $value): bool => $value !== null && $value !== []);
    }
}
