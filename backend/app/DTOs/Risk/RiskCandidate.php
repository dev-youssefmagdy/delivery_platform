<?php

namespace App\DTOs\Risk;

use App\Enums\Risk\RiskSignalType;
use App\Enums\Risk\RiskSubjectType;

/**
 * What a detector found: the type, who it is about, a 0-100 score and the facts that explain it. Not yet a stored signal.
 */
final readonly class RiskCandidate
{
    /**
     * @param array<string, mixed> $evidence counts, rates, the window and booking codes; never contact data
     */
    public function __construct(
        public RiskSignalType $type,
        public RiskSubjectType $subjectType,
        public int $subjectId,
        public int $score,
        public array $evidence,
    ) {}
}
