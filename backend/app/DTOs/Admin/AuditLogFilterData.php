<?php

namespace App\DTOs\Admin;

use App\DTOs\Concerns\ParsesFilterInput;
use App\DTOs\Contracts\FilterData;
use App\Enums\Common\AuditAction;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/**
 * Filters of the audit log listing (Plan Appendix C: actor, action, target type, target id, date range, has reason).
 */
final readonly class AuditLogFilterData implements FilterData
{
    use ParsesFilterInput;

    /**
     * @param list<AuditAction> $actions
     */
    public function __construct(
        public ?int $actorId = null,
        public array $actions = [],
        public ?string $targetType = null,
        public ?string $targetId = null,
        public ?CarbonImmutable $from = null,
        public ?CarbonImmutable $to = null,
        public ?bool $hasReason = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        $input = self::filterInput($request);
        [$from, $to] = self::dateRangeFilter($input, 'created_at');

        return new self(
            actorId: self::intFilter($input, 'actor_id'),
            actions: self::enumListFilter($input, 'actions', AuditAction::class),
            targetType: self::stringFilter($input, 'target_type', 64),
            targetId: self::stringFilter($input, 'target_id', 64),
            from: $from,
            to: $to,
            hasReason: self::boolFilter($input, 'has_reason'),
        );
    }

    public function activeCount(): int
    {
        return self::countActive([$this->actorId, $this->actions, $this->targetType, $this->targetId, $this->from ?? $this->to, $this->hasReason]);
    }

    public function toArray(): array
    {
        return array_filter([
            'actor_id' => $this->actorId,
            'actions' => array_map(static fn (AuditAction $action): string => $action->value, $this->actions),
            'target_type' => $this->targetType,
            'target_id' => $this->targetId,
            'created_at' => array_filter(['from' => $this->from?->toDateString(), 'to' => $this->to?->toDateString()]),
            'has_reason' => $this->hasReason === null ? null : (int) $this->hasReason,
        ], static fn (mixed $value): bool => $value !== null && $value !== []);
    }
}
