<?php

namespace App\Http\Controllers\Admin\Content;

use App\DataTables\Admin\Content\ContentBannersDataTable;
use App\DTOs\Content\BannerData;
use App\Enums\Content\ContentType;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\Admin\Content\DestroyContentRequest;
use App\Http\Requests\Admin\Content\SaveBannerRequest;
use App\Models\ContentBanner;
use App\Services\Audit\AuditTrailService;
use App\Services\Content\ContentEntityService;
use App\Support\Http\AdminFormResponse;
use Illuminate\Contracts\View\View;

/**
 * CMS banners (content.manage writes drafts, content.publish moves them through the workflow).
 */
final class BannerController extends AdminController
{
    public function __construct(private readonly ContentEntityService $service) {}

    public function index(ContentBannersDataTable $table): mixed
    {
        return $table->render('admin.content.banners.index');
    }

    public function create(): View
    {
        return view('admin.content.banners.form', ['banner' => null]);
    }

    public function store(SaveBannerRequest $request): AdminFormResponse
    {
        $contentBanner = $this->service->create(ContentType::Banner, BannerData::fromRequest($request), $this->actor());

        return $this->formResponse(__('content.created'))->redirect(route('admin.content-banners.show', $contentBanner));
    }

    public function show(ContentBanner $contentBanner): View
    {
        return view('admin.content.banners.show', ['banner' => $contentBanner->loadMissing(['publisher:id,name', 'creator:id,name', 'updater:id,name', 'media'])]);
    }

    public function activity(ContentBanner $contentBanner, AuditTrailService $trail): View
    {
        return view('admin.partials.activity', ['logs' => $trail->latestFor($contentBanner)]);
    }

    public function edit(ContentBanner $contentBanner): View
    {
        return view('admin.content.banners.form', ['banner' => $contentBanner->loadMissing(['media'])]);
    }

    public function update(SaveBannerRequest $request, ContentBanner $contentBanner): AdminFormResponse
    {
        $this->service->update($contentBanner, BannerData::fromRequest($request), $this->actor());

        return $this->formResponse(__('content.updated'))->redirect(route('admin.content-banners.show', $contentBanner));
    }

    public function destroy(DestroyContentRequest $request, ContentBanner $contentBanner): AdminFormResponse
    {
        $this->service->delete($contentBanner, $this->actor(), $request->reason());

        $response = $this->formResponse(__('content.deleted'))->reloadTable(ContentBannersDataTable::TABLE_ID);

        return $request->boolean('from_show') ? $response->redirect(route('admin.content-banners.index')) : $response;
    }
}
