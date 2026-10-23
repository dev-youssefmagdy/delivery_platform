<?php

namespace App\DTOs\Content;

use App\DTOs\Concerns\NormalizesInput;
use App\Enums\Content\BodyFormat;
use App\Enums\Content\PageKind;
use App\Http\Requests\Admin\Content\SavePageRequest;
use App\Support\Content\BlockFormMapper;

final readonly class PageData implements ContentData
{
    use NormalizesInput;

    /**
     * @param array<string, string|null> $title
     * @param array<string, string|null> $summary
     * @param array<string, string|null> $body
     * @param list<array<string, mixed>> $blocks
     */
    public function __construct(
        public PageKind $kind,
        public string $slug,
        public array $title,
        public array $summary,
        public BodyFormat $bodyFormat,
        public array $body,
        public array $blocks,
        public ?int $heroMediaId,
        public ?int $serviceCategoryId,
        public ?int $cityId,
        public bool $fallbackToAr,
        public SeoData $seo,
        public ?int $lockVersion = null,
    ) {}

    public static function fromRequest(SavePageRequest $request): self
    {
        /** @var array<string, mixed> $data */
        $data = $request->validated();
        $kind = PageKind::from((string) $data['kind']);

        return new self(
            kind: $kind,
            slug: (string) $data['slug'],
            title: self::translations($data['title'] ?? []),
            summary: self::translations($data['summary'] ?? []),
            bodyFormat: BodyFormat::from((string) ($data['body_format'] ?? BodyFormat::Markdown->value)),
            body: self::translations($data['body'] ?? []),
            blocks: BlockFormMapper::fromInput((array) ($data['blocks'] ?? [])),
            heroMediaId: self::id($data['hero_media_id'] ?? null),
            serviceCategoryId: $kind === PageKind::Service ? self::id($data['service_category_id'] ?? null) : null,
            cityId: $kind === PageKind::City ? self::id($data['city_id'] ?? null) : null,
            fallbackToAr: (bool) ($data['fallback_to_ar'] ?? true),
            seo: SeoData::fromValidated($data),
            lockVersion: isset($data['lock_version']) ? (int) $data['lock_version'] : null,
        );
    }

    public function attributes(): array
    {
        return [
            'kind' => $this->kind,
            'slug' => $this->slug,
            'title' => self::filled($this->title),
            'summary' => self::filled($this->summary),
            'body_format' => $this->bodyFormat,
            'body' => self::filled($this->body),
            'blocks' => $this->blocks,
            'hero_media_id' => $this->heroMediaId,
            'service_category_id' => $this->serviceCategoryId,
            'city_id' => $this->cityId,
            'fallback_to_ar' => $this->fallbackToAr,
        ] + $this->seo->attributes();
    }

    public function lockVersion(): ?int
    {
        return $this->lockVersion;
    }
}
