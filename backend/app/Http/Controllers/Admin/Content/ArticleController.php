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
        $article = $this->service->create(ContentType::Article, ArticleData::fromRequest($request), $this->actor());

        return $this->formResponse(__('admin.content.created'))->redirect(route('admin.content-articles.show', $article));
    }

    public function show(ContentArticle $article): View
    {
        return view('admin.content.articles.show', ['article' => $article->loadMissing(['publisher:id,name', 'creator:id,name', 'updater:id,name'])]);
    }

    public function activity(ContentArticle $article, AuditTrailService $trail): View
    {
        return view('admin.partials.activity', ['logs' => $trail->latestFor($article)]);
    }

    public function edit(ContentArticle $article): View
    {
        return view('admin.content.articles.form', ['article' => $article]);
    }

    public function update(SaveArticleRequest $request, ContentArticle $article): AdminFormResponse
    {
        $this->service->update($article, ArticleData::fromRequest($request), $this->actor());

        return $this->formResponse(__('admin.content.updated'))->redirect(route('admin.content-articles.show', $article));
    }

    public function destroy(DestroyContentRequest $request, ContentArticle $article): AdminFormResponse
    {
        $this->service->delete($article, $this->actor(), $request->reason());

        $response = $this->formResponse(__('admin.content.deleted'))->reloadTable(ContentArticlesDataTable::TABLE_ID);

        return $request->boolean('from_show') ? $response->redirect(route('admin.content-articles.index')) : $response;
    }
}
