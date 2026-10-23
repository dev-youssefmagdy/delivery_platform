<?php

namespace App\Http\Controllers\Admin\Content;

use App\DataTables\Admin\Content\ContentArticlesDataTable;
use App\DTOs\Content\ArticleData;
use App\Enums\Content\ContentType;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\Admin\Content\DestroyContentRequest;
use App\Http\Requests\Admin\Content\SaveArticleRequest;
use App\Models\ContentArticle;
use App\Services\Audit\AuditTrailService;
use App\Services\Content\ContentEntityService;
use App\Support\Http\AdminFormResponse;
use Illuminate\Contracts\View\View;

/**
 * CMS articles (content.manage writes drafts, content.publish moves them through the workflow).
 */
final class ArticleController extends AdminController
{
    public function __construct(private readonly ContentEntityService $service) {}

    public function index(ContentArticlesDataTable $table): mixed
    {
        return $table->render('admin.content.articles.index');
    }

    public function create(): View
    {
        return view('admin.content.articles.form', ['article' => null]);
    }

    public function store(SaveArticleRequest $request): AdminFormResponse
    {
        $contentArticle = $this->service->create(ContentType::Article, ArticleData::fromRequest($request), $this->actor());

        return $this->formResponse(__('content.created'))->redirect(route('admin.content-articles.show', $contentArticle));
    }

    public function show(ContentArticle $contentArticle): View
    {
        return view('admin.content.articles.show', ['article' => $contentArticle->loadMissing(['publisher:id,name', 'creator:id,name', 'updater:id,name', 'category', 'cover', 'ogImage'])]);
    }

    public function activity(ContentArticle $contentArticle, AuditTrailService $trail): View
    {
        return view('admin.partials.activity', ['logs' => $trail->latestFor($contentArticle)]);
    }

    public function edit(ContentArticle $contentArticle): View
    {
        return view('admin.content.articles.form', ['article' => $contentArticle->loadMissing(['category', 'cover', 'ogImage'])]);
    }

    public function update(SaveArticleRequest $request, ContentArticle $contentArticle): AdminFormResponse
    {
        $this->service->update($contentArticle, ArticleData::fromRequest($request), $this->actor());

        return $this->formResponse(__('content.updated'))->redirect(route('admin.content-articles.show', $contentArticle));
    }

    public function destroy(DestroyContentRequest $request, ContentArticle $contentArticle): AdminFormResponse
    {
        $this->service->delete($contentArticle, $this->actor(), $request->reason());

        $response = $this->formResponse(__('content.deleted'))->reloadTable(ContentArticlesDataTable::TABLE_ID);

        return $request->boolean('from_show') ? $response->redirect(route('admin.content-articles.index')) : $response;
    }
}
