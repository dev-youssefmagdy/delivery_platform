<?php

namespace App\DTOs\Content;

use App\DTOs\Concerns\NormalizesInput;
use App\Http\Requests\Admin\Content\SaveFaqRequest;

final readonly class FaqData implements ContentData
{
    use NormalizesInput;

    /**
     * @param array<string, string|null> $question
     * @param array<string, string|null> $answer
     */
    public function __construct(
        public ?int $categoryId,
        public ?int $serviceCategoryId,
        public array $question,
        public array $answer,
        public int $sortOrder,
        public bool $fallbackToAr,
        public ?int $lockVersion = null,
    ) {}

    public static function fromRequest(SaveFaqRequest $request): self
    {
        /** @var array<string, mixed> $data */
        $data = $request->validated();

        return new self(
            categoryId: self::id($data['category_id'] ?? null),
            serviceCategoryId: self::id($data['service_category_id'] ?? null),
            question: self::translations($data['question'] ?? []),
            answer: self::translations($data['answer'] ?? []),
            sortOrder: (int) ($data['sort_order'] ?? 0),
            fallbackToAr: (bool) ($data['fallback_to_ar'] ?? true),
            lockVersion: isset($data['lock_version']) ? (int) $data['lock_version'] : null,
        );
    }

    public function attributes(): array
    {
        return [
            'category_id' => $this->categoryId,
            'service_category_id' => $this->serviceCategoryId,
            'question' => self::filled($this->question),
            'answer' => self::filled($this->answer),
            'sort_order' => $this->sortOrder,
            'fallback_to_ar' => $this->fallbackToAr,
        ];
    }

    public function lockVersion(): ?int
    {
        return $this->lockVersion;
    }
}
