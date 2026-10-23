<?php

namespace App\Http\Controllers\Admin\Content;

use App\DataTables\Admin\Content\ContentMenusDataTable;
use App\DTOs\Content\MenuData;
use App\Enums\Content\ContentType;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\Admin\Content\DestroyContentRequest;
use App\Http\Requests\Admin\Content\SaveMenuRequest;
use App\Models\ContentMenu;
use App\Services\Audit\AuditTrailService;
use App\Services\Content\ContentEntityService;
use App\Support\Http\AdminFormResponse;
use Illuminate\Contracts\View\View;

/**
 * CMS menus (content.manage writes drafts, content.publish moves them through the workflow).
 */
final class MenuController extends AdminController
{
    public function __construct(private readonly ContentEntityService $service) {}

    public function index(ContentMenusDataTable $table): mixed
    {
        return $table->render('admin.content.menus.index');
    }

    public function create(): View
    {
        return view('admin.content.menus.form', ['menu' => null]);
    }

    public function store(SaveMenuRequest $request): AdminFormResponse
    {
        $contentMenu = $this->service->create(ContentType::Menu, MenuData::fromRequest($request), $this->actor());

        return $this->formResponse(__('content.created'))->redirect(route('admin.content-menus.show', $contentMenu));
    }

    public function show(ContentMenu $contentMenu): View
    {
        return view('admin.content.menus.show', ['menu' => $contentMenu->loadMissing(['publisher:id,name', 'creator:id,name', 'updater:id,name'])]);
    }

    public function activity(ContentMenu $contentMenu, AuditTrailService $trail): View
    {
        return view('admin.partials.activity', ['logs' => $trail->latestFor($contentMenu)]);
    }

    public function edit(ContentMenu $contentMenu): View
    {
        return view('admin.content.menus.form', ['menu' => $contentMenu]);
    }

    public function update(SaveMenuRequest $request, ContentMenu $contentMenu): AdminFormResponse
    {
        $this->service->update($contentMenu, MenuData::fromRequest($request), $this->actor());

        return $this->formResponse(__('content.updated'))->redirect(route('admin.content-menus.show', $contentMenu));
    }

    public function destroy(DestroyContentRequest $request, ContentMenu $contentMenu): AdminFormResponse
    {
        $this->service->delete($contentMenu, $this->actor(), $request->reason());

        $response = $this->formResponse(__('content.deleted'))->reloadTable(ContentMenusDataTable::TABLE_ID);

        return $request->boolean('from_show') ? $response->redirect(route('admin.content-menus.index')) : $response;
    }
}
