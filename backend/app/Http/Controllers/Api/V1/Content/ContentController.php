<?php

namespace App\Http\Controllers\Api\V1\Content;

use App\Enums\Content\ArticleType;
use App\Enums\Policy\PolicyType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Content\ArticlesRequest;
use App\Http\Requests\Api\V1\Content\BannersRequest;
use App\Http\Requests\Api\V1\Content\FaqsRequest;
use App\Services\Content\ContentReadService;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Public CMS content for the apps (help center, banners, FAQs, pages). Published content only, localized
 * (Accept-Language, Arabic fallback where the item allows it), cached with an ETag. Everything comes from
 * ContentReadService, the same service the website uses.
 */
final class ContentController extends Controller
{
    private const HEADERS = ['Vary' => 'Accept-Language'];

    public function __construct(private readonly ContentReadService $content) {}

    public function banners(BannersRequest $request): JsonResponse
    {
        return ApiResponse::success(data: $this->content->banners($request->placements(), $request->audience()), headers: self::HEADERS);
    }

    public function faqs(FaqsRequest $request): JsonResponse
    {
        $faqs = $this->content->faqs($request->validated('category'), $request->validated('service'));

        return ApiResponse::success(data: $faqs, headers: self::HEADERS);
    }

    public function page(string $slug): JsonResponse
    {
        $page = $this->content->page($slug) ?? throw (new ModelNotFoundException)->setModel(\App\Models\ContentPage::class);

        return ApiResponse::success(data: $page, headers: self::HEADERS);
    }

    public function articles(ArticlesRequest $request): JsonResponse
    {
        $articles = $this->content->articles(
            ArticleType::tryFrom((string) $request->validated('type')),
            $request->validated('category'),
            (int) ($request->validated('limit') ?? 100),
        );

        return ApiResponse::success(data: $articles, headers: self::HEADERS);
    }

    public function article(string $slug): JsonResponse
    {
        $article = $this->content->article($slug) ?? throw (new ModelNotFoundException)->setModel(\App\Models\ContentArticle::class);

        return ApiResponse::success(data: $article, headers: self::HEADERS);
    }

    public function menus(): JsonResponse
    {
        return ApiResponse::success(data: $this->content->menus(), headers: self::HEADERS);
    }

    public function appLinks(): JsonResponse
    {
        return ApiResponse::success(data: $this->content->appLinks());
    }

    public function legal(string $type): JsonResponse
    {
        return ApiResponse::success(data: $this->content->policy(PolicyType::from($type)), headers: self::HEADERS);
    }
}
