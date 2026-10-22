<?php

namespace App\DTOs\Notification;

use App\Enums\Notification\CampaignAudience;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/**
 * Input of the push campaign form. `scheduledAt` is stored in UTC; the form is typed in the market timezone.
 */
final readonly class PushCampaignData
{
    public function __construct(
        public string $name,
        public CampaignAudience $audience,
        public string $titleAr,
        public string $bodyAr,
        public ?string $titleEn,
        public ?string $bodyEn,
        public ?CarbonImmutable $scheduledAt,
        public ?int $lockVersion = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $scheduled = trim((string) $request->input('scheduled_at'));
        $timezone = (string) config('admin.display_timezone', 'UTC');

        return new self(
            name: trim((string) $request->input('name')),
            audience: CampaignAudience::from((string) $request->input('audience')),
            titleAr: trim((string) $request->input('title.ar')),
            bodyAr: trim((string) $request->input('body.ar')),
            titleEn: self::nullable($request->input('title.en')),
            bodyEn: self::nullable($request->input('body.en')),
            scheduledAt: $scheduled === '' ? null : CarbonImmutable::createFromFormat('Y-m-d H:i', $scheduled, $timezone)->utc(),
            lockVersion: $request->filled('lock_version') ? $request->integer('lock_version') : null,
        );
    }

    private static function nullable(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : '';

        return $value === '' ? null : $value;
    }
}
