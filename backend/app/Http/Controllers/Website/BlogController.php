<?php

namespace App\Http\Controllers\Website;

use App\Enums\Content\ArticleType;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;

/**
 * Blog / news (optional SEO articles): published `blog` articles, newest first, with Article structured data.
 */
final class BlogController extends SiteController
{
    public function index(): View
    {
        $articles = $this->content->articles(ArticleType::Blog, null, 100, $this->locale());
        $meta = $this->meta->make('blog', [], (string) __('site.blog.title'), (string) __('site.blog.description'));

        return $this->page('website.pages.blog', ['articles' => $articles], $meta);
    }

    public function article(string $slug): View|Response
    {
        $article = $this->content->article($slug, $this->locale());

        if ($article === null || $article['type'] !== ArticleType::Blog->value) {
            return $this->notFound();
        }

        $meta = $this->meta->make('blog-article', ['slug' => $slug], $article['title'], $article['summary'], $article['seo'], $article['cover_image'], 'article', [[
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $article['title'],
            'description' => $article['summary'],
            'datePublished' => $article['published_at'],
            'dateModified' => $article['updated_at'],
            'inLanguage' => $article['locale'],
            'image' => $article['cover_image']['url'] ?? null,
            'mainEntityOfPage' => $this->paths->url('blog-article', ['slug' => $slug]),
            'publisher' => ['@type' => 'Organization', 'name' => (string) config('website.brand.name')],
        ]]);

        return $this->page('website.pages.article', ['article' => $article, 'backKey' => 'blog', 'backLabel' => __('site.blog.title')], $meta);
    }
}
