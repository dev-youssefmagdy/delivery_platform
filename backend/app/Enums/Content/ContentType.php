<?php

namespace App\Enums\Content;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;
use App\Models\ContentArticle;
use App\Models\ContentBanner;
use App\Models\ContentFaq;
use App\Models\ContentMenu;
use App\Models\ContentPage;
use InvalidArgumentException;

/**
 * Content entities that go through the workflow (they share routes for transitions, preview and revisions).
 */
enum ContentType: string implements HasLabel
{
    use EnumHelpers;

    case Page = 'page';
    case Article = 'article';
    case Faq = 'faq';
    case Banner = 'banner';
    case Menu = 'menu';

    public function label(): string
    {
        return __("enums.content_type.{$this->value}");
    }

    /**
     * @return class-string<ContentPage|ContentArticle|ContentFaq|ContentBanner|ContentMenu>
     */
    public function modelClass(): string
    {
        return match ($this) {
            self::Page => ContentPage::class,
            self::Article => ContentArticle::class,
            self::Faq => ContentFaq::class,
            self::Banner => ContentBanner::class,
            self::Menu => ContentMenu::class,
        };
    }

    public function routePrefix(): string
    {
        return match ($this) {
            self::Page => 'admin.content-pages',
            self::Article => 'admin.content-articles',
            self::Faq => 'admin.content-faqs',
            self::Banner => 'admin.content-banners',
            self::Menu => 'admin.content-menus',
        };
    }

    public static function forModel(object $model): self
    {
        foreach (self::cases() as $case) {
            if ($model instanceof ($case->modelClass())) {
                return $case;
            }
        }

        throw new InvalidArgumentException('Not a workflow content model: '.$model::class);
    }
}
