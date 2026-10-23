<?php

namespace App\DTOs\Content;

use App\DTOs\Concerns\NormalizesInput;
use App\Enums\Content\ArticleType;
use App\Enums\Content\BodyFormat;
use App\Http\Requests\Admin\Content\SaveArticleRequest;

final readonly class ArticleData implements ContentData
{
    use NormalizesInput;

    /**
     * @param array<string, string|null> $title
     * @param array<string, string|null> $summary
     * @param array<string, string|null> $body
     */
    public function __construct(
        public ArticleType $type,
        public string $slug,
        public ?int $categoryId,
        public array $title,
        public array $summary,
        public BodyFormat $bodyFormat,
        public array $body,
        public ?int $coverMediaId,
        public int $sortOrder,
        public bool $fallbackToAr,
        public SeoData $seo,
        public ?int $lockVersion = null,
    ) {}

    public static function fromRequest(SaveArticleRequest $request): self
    {
        /** @var array<string, mixed> $data */
        $data = $request->validated();

        return new self(
            type: ArticleType::from((string) $data['type']),
            slug: (string) $data['slug'],
            categoryId: self::id($data['category_id'] ?? null),
            title: self::translations($data['title'] ?? []),
            summary: self::translations($data['summary'] ?? []),
            bodyFormat: BodyFormat::from((string) ($data['body_format'] ?? BodyFormat::Markdown->value)),
            body: self::translations($data['body'] ?? []),
            coverMediaId: self::id($data['cover_media_id'] ?? null),
            sortOrder: (int) ($data['sort_order'] ?? 0),
            fallbackToAr: (bool) ($data['fallback_to_ar'] ?? true),
            seo: SeoData::fromValidated($data),
            lockVersion: isset($data['lock_version']) ? (int) $data['lock_version'] : null,
        );
    }

    public function attributes(): array
    {
        return [
            'type' => $this->type,
            'slug' => $this->slug,
            'category_id' => $this->categoryId,
            'title' => self::filled($this->title),
            'summary' => self::filled($this->summary),
            'body_format' => $this->bodyFormat,
            'body' => self::filled($this->body),
            'cover_media_id' => $this->coverMediaId,
            'sort_order' => $this->sortOrder,
            'fallback_to_ar' => $this->fallbackToAr,
        ] + $this->seo->attributes();
    }

    public function lockVersion(): ?int
    {
        return $this->lockVersion;
    }
}
