<?php

namespace App\Http\Controllers\Admin\Content;

use App\DataTables\Admin\Content\ContentPagesDataTable;
use App\DTOs\Content\PageData;
use App\Enums\Content\ContentType;
use App\Enums\Content\PageKind;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\Admin\Content\DestroyContentRequest;
use App\Http\Requests\Admin\Content\SavePageRequest;
use App\Models\ContentPage;
use App\Services\Audit\AuditTrailService;
use App\Services\Content\ContentEntityService;
use App\Support\Http\AdminFormResponse;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * CMS pages (content.manage writes drafts, content.publish moves them through the workflow).
 */
final class PageController extends AdminController
{
    public function __construct(private readonly ContentEntityService $service) {}

    public function index(ContentPagesDataTable $table): mixed
    {
        return $table->render('admin.content.pages.index');
    }

    public function create(Request $request): View
    {
        return view('admin.content.pages.form', ['page' => null, 'kind' => PageKind::tryFrom((string) $request->query('kind')) ?? PageKind::Generic]);
    }

    public function store(SavePageRequest $request): AdminFormResponse
    {
        $contentPage = $this->service->create(ContentType::Page, PageData::fromRequest($request), $this->actor());

        return $this->formResponse(__('content.created'))->redirect(route('admin.content-pages.show', $contentPage));
    }

    public function show(ContentPage $contentPage): View
    {
        return view('admin.content.pages.show', ['page' => $contentPage->loadMissing(['publisher:id,name', 'creator:id,name', 'updater:id,name', 'hero', 'ogImage', 'serviceCategory', 'city'])]);
    }

    public function activity(ContentPage $contentPage, AuditTrailService $trail): View
    {
        return view('admin.partials.activity', ['logs' => $trail->latestFor($contentPage)]);
    }

    public function edit(ContentPage $contentPage): View
    {
        return view('admin.content.pages.form', ['page' => $contentPage->loadMissing(['hero', 'ogImage', 'serviceCategory', 'city'])]);
    }

    public function update(SavePageRequest $request, ContentPage $contentPage): AdminFormResponse
    {
        $this->service->update($contentPage, PageData::fromRequest($request), $this->actor());

        return $this->formResponse(__('content.updated'))->redirect(route('admin.content-pages.show', $contentPage));
    }

    public function destroy(DestroyContentRequest $request, ContentPage $contentPage): AdminFormResponse
    {
        $this->service->delete($contentPage, $this->actor(), $request->reason());

        $response = $this->formResponse(__('content.deleted'))->reloadTable(ContentPagesDataTable::TABLE_ID);

        return $request->boolean('from_show') ? $response->redirect(route('admin.content-pages.index')) : $response;
    }
}
