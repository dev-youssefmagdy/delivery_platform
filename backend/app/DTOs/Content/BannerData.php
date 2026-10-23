<?php

namespace App\DTOs\Content;

use App\DTOs\Concerns\NormalizesInput;
use App\Enums\Content\BannerAudience;
use App\Enums\Content\BannerKind;
use App\Enums\Content\BannerPlacement;
use App\Http\Requests\Admin\Content\SaveBannerRequest;
use Carbon\CarbonImmutable;

final readonly class BannerData implements ContentData
{
    use NormalizesInput;

    /**
     * @param array<string, string|null> $title
     * @param array<string, string|null> $body
     * @param array<string, string|null> $ctaLabel
     */
    public function __construct(
        public BannerKind $kind,
        public BannerPlacement $placement,
        public BannerAudience $audience,
        public array $title,
        public array $body,
        public ?int $mediaId,
        public array $ctaLabel,
        public ?string $ctaUrl,
        public ?CarbonImmutable $startsAt,
        public ?CarbonImmutable $endsAt,
        public int $sortOrder,
        public bool $fallbackToAr,
        public ?int $lockVersion = null,
    ) {}

    public static function fromRequest(SaveBannerRequest $request): self
    {
        /** @var array<string, mixed> $data */
        $data = $request->validated();
        $timezone = (string) config('admin.display_timezone');

        return new self(
            kind: BannerKind::from((string) $data['kind']),
            placement: BannerPlacement::from((string) $data['placement']),
            audience: BannerAudience::from((string) ($data['audience'] ?? BannerAudience::All->value)),
            title: self::translations($data['title'] ?? []),
            body: self::translations($data['body'] ?? []),
            mediaId: self::id($data['media_id'] ?? null),
            ctaLabel: self::translations($data['cta_label'] ?? []),
            ctaUrl: self::text($data['cta_url'] ?? null),
            startsAt: self::text($data['starts_at'] ?? null) ? CarbonImmutable::parse((string) $data['starts_at'], $timezone)->utc() : null,
            endsAt: self::text($data['ends_at'] ?? null) ? CarbonImmutable::parse((string) $data['ends_at'], $timezone)->utc() : null,
            sortOrder: (int) ($data['sort_order'] ?? 0),
            fallbackToAr: (bool) ($data['fallback_to_ar'] ?? true),
            lockVersion: isset($data['lock_version']) ? (int) $data['lock_version'] : null,
        );
    }

    public function attributes(): array
    {
        return [
            'kind' => $this->kind,
            'placement' => $this->placement,
            'audience' => $this->audience,
            'title' => self::filled($this->title),
            'body' => self::filled($this->body),
            'media_id' => $this->kind === BannerKind::Banner ? $this->mediaId : null,
            'cta_label' => self::filled($this->ctaLabel),
            'cta_url' => $this->ctaUrl,
            'starts_at' => $this->startsAt,
            'ends_at' => $this->endsAt,
            'sort_order' => $this->sortOrder,
            'fallback_to_ar' => $this->fallbackToAr,
        ];
    }

    public function lockVersion(): ?int
    {
        return $this->lockVersion;
    }
}
