<?php

namespace App\Services\Content;

use App\Enums\Content\ContentType;
use App\Models\Admin;
use App\Models\ContentRevision;
use App\Repositories\Contracts\Content\ContentRevisionRepositoryInterface;
use BackedEnum;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Revision history: a snapshot of the editable fields every time an entity is saved or changes status.
 * Must run inside the transaction that holds the entity row lock (the revision number is max + 1).
 */
final class ContentRevisionService
{
    public function __construct(private readonly ContentRevisionRepositoryInterface $revisions) {}

    public function capture(Model $content, ?Admin $actor, string $action, ?string $reason = null): ContentRevision
    {
        $type = ContentType::forModel($content);

        return $this->revisions->append([
            'revisionable_type' => $type->value,
            'revisionable_id' => $content->getKey(),
            'number' => $this->revisions->nextNumber($type, (int) $content->getKey()),
            'action' => $action,
            'status' => $content->getAttribute('status'),
            'snapshot' => $this->snapshot($content),
            'reason' => $reason,
            'admin_id' => $actor?->getKey(),
        ]);
    }

    /**
     * @return Collection<int, ContentRevision>
     */
    public function history(Model $content): Collection
    {
        return $this->revisions->forEntity(ContentType::forModel($content), (int) $content->getKey());
    }

    public function find(Model $content, int $number): ?ContentRevision
    {
        return $this->revisions->findForEntity(ContentType::forModel($content), (int) $content->getKey(), $number);
    }

    /**
     * Editable fields only (no workflow columns, no derived HTML): restoring re-derives the HTML.
     *
     * @return array<string, mixed>
     */
    public function snapshot(Model $content): array
    {
        $skip = array_merge(\App\Models\ContentPage::WORKFLOW, ['body_html', 'answer_html']);
        $translatable = method_exists($content, 'getTranslatableAttributes') ? $content->getTranslatableAttributes() : [];
        $snapshot = [];

        foreach ($content->getFillable() as $attribute) {
            if (in_array($attribute, $skip, true)) {
                continue;
            }

            $value = in_array($attribute, $translatable, true) && method_exists($content, 'getTranslations')
                ? $content->getTranslations($attribute)
                : $content->getAttribute($attribute);

            $snapshot[$attribute] = match (true) {
                $value instanceof BackedEnum => $value->value,
                $value instanceof DateTimeInterface => $value->format(DATE_ATOM),
                default => $value,
            };
        }

        if (isset($snapshot['blocks']) && is_array($snapshot['blocks'])) {
            $snapshot['blocks'] = array_map(static function (mixed $block): mixed {
                if (is_array($block)) {
                    unset($block['body_html']);
                }

                return $block;
            }, $snapshot['blocks']);
        }

        return $snapshot;
    }
}
