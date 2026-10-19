<?php

namespace App\DTOs\Policy;

use App\Enums\Policy\PolicyType;
use App\Http\Requests\Admin\Policy\StorePolicyVersionRequest;
use App\Http\Requests\Admin\Policy\UpdatePolicyVersionRequest;

/**
 * Validated input for creating/updating a policy draft.
 */
final readonly class PolicyVersionData
{
    /**
     * @param array<string, string|null> $title translations keyed by locale
     * @param array<string, string|null> $content translations keyed by locale
     */
    public function __construct(
        public PolicyType $type,
        public array $title,
        public array $content,
        public ?string $changeNote,
        public ?int $lockVersion = null,
    ) {}

    public static function fromRequest(StorePolicyVersionRequest|UpdatePolicyVersionRequest $request): self
    {
        /** @var array<string, mixed> $data */
        $data = $request->validated();

        return new self(
            type: PolicyType::from((string) $data['type']),
            title: self::translations($data['title'] ?? []),
            content: self::translations($data['content'] ?? []),
            changeNote: isset($data['change_note']) && trim((string) $data['change_note']) !== '' ? trim((string) $data['change_note']) : null,
            lockVersion: isset($data['lock_version']) ? (int) $data['lock_version'] : null,
        );
    }

    /**
     * Model attributes (status, version and lock_version are managed by the service).
     *
     * @return array<string, mixed>
     */
    public function attributes(): array
    {
        return [
            'type' => $this->type,
            'title' => array_filter($this->title, static fn (?string $value): bool => $value !== null),
            'content' => array_filter($this->content, static fn (?string $value): bool => $value !== null),
            'change_note' => $this->changeNote,
        ];
    }

    /**
     * @return array<string, string|null>
     */
    private static function translations(mixed $input): array
    {
        $translations = [];

        foreach ((array) $input as $locale => $value) {
            $translations[(string) $locale] = is_string($value) && trim($value) !== '' ? trim($value) : null;
        }

        return $translations;
    }
}
