<?php

namespace App\Http\Controllers\Website;

use App\Enums\Content\ArticleType;
use App\Support\Content\HtmlSanitizer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;

/**
 * FAQ / help center (SRS 17.2): published FAQs grouped by category, help articles, and the FAQPage structured data (emitted
 * only when there is at least one complete question and answer, as search engines require).
 */
final class HelpController extends SiteController
{
    public function index(HtmlSanitizer $sanitizer): View
    {
        $faqs = $this->content->faqs(null, null, $this->locale());
        $articles = $this->content->articles(ArticleType::Help, null, 100, $this->locale());

        $entities = [];

        foreach ($faqs['faqs'] as $faq) {
            $answer = $sanitizer->plainText($faq['answer_html']);

            if (trim($faq['question']) !== '' && $answer !== '') {
                $entities[] = ['@type' => 'Question', 'name' => $faq['question'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $answer]];
            }
        }

        $meta = $this->meta->make('help', [], (string) __('site.help.title'), (string) __('site.help.description'), jsonLd: $entities === [] ? [] : [
            ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $entities],
        ]);

        return $this->page('website.pages.help', ['faqs' => $faqs, 'articles' => $articles], $meta);
    }

    public function article(string $slug): View|Response
    {
        $article = $this->content->article($slug, $this->locale());

        if ($article === null || $article['type'] !== ArticleType::Help->value) {
            return $this->notFound();
        }

        $meta = $this->meta->make('help-article', ['slug' => $slug], $article['title'], $article['summary'], $article['seo'], $article['cover_image'], 'article');

        return $this->page('website.pages.article', ['article' => $article, 'backKey' => 'help', 'backLabel' => __('site.help.title')], $meta);
    }
}
