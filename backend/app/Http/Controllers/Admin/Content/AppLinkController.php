<?php

namespace App\Http\Controllers\Admin\Content;

use App\DataTables\Admin\Content\ContentAppLinksDataTable;
use App\DTOs\Content\AppLinkData;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\Admin\Content\DestroyContentRequest;
use App\Http\Requests\Admin\Content\SaveAppLinkRequest;
use App\Models\ContentAppLink;
use App\Services\Audit\AuditTrailService;
use App\Services\Content\ContentAppLinkService;
use App\Support\Http\AdminFormResponse;
use Illuminate\Contracts\View\View;

/**
 * AppLink administration (content.manage). Every change is audited and the public cache is refreshed.
 */
final class AppLinkController extends AdminController
{
    public function __construct(private readonly ContentAppLinkService $service) {}

    public function index(ContentAppLinksDataTable $table): mixed
    {
        return $table->render('admin.content.app-links.index');
    }

    public function create(): View
    {
        return view('admin.content.app-links.form', ['link' => null]);
    }

    public function store(SaveAppLinkRequest $request): AdminFormResponse
    {
        $this->service->create(AppLinkData::fromRequest($request), $this->actor());

        return $this->formResponse(__('content.created'))->redirect(route('admin.content-app-links.index'));
    }

    public function edit(ContentAppLink $link): View
    {
        return view('admin.content.app-links.form', ['link' => $link]);
    }

    public function update(SaveAppLinkRequest $request, ContentAppLink $link): AdminFormResponse
    {
        $this->service->update($link, AppLinkData::fromRequest($request), $this->actor());

        return $this->formResponse(__('content.updated'))->redirect(route('admin.content-app-links.index'));
    }

    public function destroy(DestroyContentRequest $request, ContentAppLink $link): AdminFormResponse
    {
        $this->service->delete($link, $this->actor(), $request->reason());

        return $this->formResponse(__('content.deleted'))->reloadTable(ContentAppLinksDataTable::TABLE_ID);
    }

    public function activity(ContentAppLink $link, AuditTrailService $trail): View
    {
        return view('admin.partials.activity', ['logs' => $trail->latestFor($link)]);
    }
}
