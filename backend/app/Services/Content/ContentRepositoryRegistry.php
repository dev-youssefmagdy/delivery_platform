<?php

namespace App\Services\Content;

use App\Enums\Content\ContentType;
use App\Repositories\Contracts\Content\ArticleRepositoryInterface;
use App\Repositories\Contracts\Content\BannerRepositoryInterface;
use App\Repositories\Contracts\Content\ContentRepositoryInterface;
use App\Repositories\Contracts\Content\FaqRepositoryInterface;
use App\Repositories\Contracts\Content\MenuRepositoryInterface;
use App\Repositories\Contracts\Content\PageRepositoryInterface;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\Model;

/**
 * Resolves the repository of a workflow content type, so the workflow and revision services stay generic.
 */
final class ContentRepositoryRegistry
{
    public function __construct(private readonly Container $container) {}

    /**
     * @return ContentRepositoryInterface<Model>
     */
    public function for(ContentType $type): ContentRepositoryInterface
    {
        /** @var ContentRepositoryInterface<Model> $repository */
        $repository = $this->container->make(match ($type) {
            ContentType::Page => PageRepositoryInterface::class,
            ContentType::Article => ArticleRepositoryInterface::class,
            ContentType::Faq => FaqRepositoryInterface::class,
            ContentType::Banner => BannerRepositoryInterface::class,
            ContentType::Menu => MenuRepositoryInterface::class,
        });

        return $repository;
    }
}
