<?php

namespace App\Support\Admin\Lookups;

use App\DTOs\Admin\LookupOptionData;
use App\Enums\Admin\AdminPermission;
use App\Models\ContentMedia;
use App\Repositories\Contracts\Content\ContentMediaRepositoryInterface;

/**
 * Media library images for the image pickers of the CMS forms (content.manage or content.publish).
 */
final class ContentMediaLookup implements Lookup
{
    public function __construct(private readonly ContentMediaRepositoryInterface $media) {}

    public function permission(): ?AdminPermission
    {
        return AdminPermission::ContentManage;
    }

    public function search(string $term, int $limit): array
    {
        return $this->map($this->media->lookup($term, $limit)->all());
    }

    public function resolve(array $ids): array
    {
        return $this->map($this->media->findManyByIds($ids)->all());
    }

    /**
     * @param array<int, ContentMedia> $items
     * @return list<LookupOptionData>
     */
    private function map(array $items): array
    {
        return array_values(array_map(
            static fn (ContentMedia $media): LookupOptionData => new LookupOptionData($media->id, '#'.$media->id.' · '.$media->original_name, ['width' => $media->width, 'height' => $media->height]),
            $items,
        ));
    }
}
