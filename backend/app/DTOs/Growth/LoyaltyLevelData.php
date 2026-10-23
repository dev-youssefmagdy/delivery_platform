<?php

namespace App\DTOs\Growth;

use Illuminate\Http\Request;

/**
 * Admin input of a loyalty level. Money is integer minor units.
 */
final readonly class LoyaltyLevelData
{
    public function __construct(
        public string $name,
        public ?string $nameEn,
        public int $minCompletedJobs,
        public int $rewardMinor,
        public int $creditValidDays,
        public int $sort,
        public ?int $lockVersion,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            name: trim((string) $request->input('name')),
            nameEn: is_string($request->input('name_en')) && trim((string) $request->input('name_en')) !== '' ? trim((string) $request->input('name_en')) : null,
            minCompletedJobs: max(1, (int) $request->input('min_completed_jobs', 1)),
            rewardMinor: max(0, (int) $request->input('reward_minor', 0)),
            creditValidDays: max(1, (int) $request->input('credit_valid_days', 30)),
            sort: (int) $request->input('sort', 0),
            lockVersion: is_numeric($request->input('lock_version')) ? (int) $request->input('lock_version') : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function attributes(): array
    {
        return [
            'name' => $this->name, 'name_en' => $this->nameEn, 'min_completed_jobs' => $this->minCompletedJobs, 'reward_minor' => $this->rewardMinor,
            'credit_valid_days' => $this->creditValidDays, 'sort' => $this->sort,
        ];
    }
}
