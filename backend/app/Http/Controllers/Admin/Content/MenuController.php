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
        $menu = $this->service->create(ContentType::Menu, MenuData::fromRequest($request), $this->actor());

        return $this->formResponse(__('admin.content.created'))->redirect(route('admin.content-menus.show', $menu));
    }

    public function show(ContentMenu $menu): View
    {
        return view('admin.content.menus.show', ['menu' => $menu->loadMissing(['publisher:id,name', 'creator:id,name', 'updater:id,name'])]);
    }

    public function activity(ContentMenu $menu, AuditTrailService $trail): View
    {
        return view('admin.partials.activity', ['logs' => $trail->latestFor($menu)]);
    }

    public function edit(ContentMenu $menu): View
    {
        return view('admin.content.menus.form', ['menu' => $menu]);
    }

    public function update(SaveMenuRequest $request, ContentMenu $menu): AdminFormResponse
    {
        $this->service->update($menu, MenuData::fromRequest($request), $this->actor());

        return $this->formResponse(__('admin.content.updated'))->redirect(route('admin.content-menus.show', $menu));
    }

    public function destroy(DestroyContentRequest $request, ContentMenu $menu): AdminFormResponse
    {
        $this->service->delete($menu, $this->actor(), $request->reason());

        $response = $this->formResponse(__('admin.content.deleted'))->reloadTable(ContentMenusDataTable::TABLE_ID);

        return $request->boolean('from_show') ? $response->redirect(route('admin.content-menus.index')) : $response;
    }
}
