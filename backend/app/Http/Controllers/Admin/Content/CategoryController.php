<?php

namespace App\Http\Controllers\Admin\Content;

use App\DataTables\Admin\Content\ContentCategoriesDataTable;
use App\DTOs\Content\CategoryData;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\Admin\Content\DestroyContentRequest;
use App\Http\Requests\Admin\Content\SaveCategoryRequest;
use App\Models\ContentCategory;
use App\Services\Audit\AuditTrailService;
use App\Services\Content\ContentCategoryService;
use App\Support\Http\AdminFormResponse;
use Illuminate\Contracts\View\View;

/**
 * Category administration (content.manage). Every change is audited and the public cache is refreshed.
 */
final class CategoryController extends AdminController
{
    public function __construct(private readonly ContentCategoryService $service) {}

    public function index(ContentCategoriesDataTable $table): mixed
    {
        return $table->render('admin.content.categories.index');
    }

    public function create(): View
    {
        return view('admin.content.categories.form', ['category' => null]);
    }

    public function store(SaveCategoryRequest $request): AdminFormResponse
    {
        $this->service->create(CategoryData::fromRequest($request), $this->actor());

        return $this->formResponse(__('content.created'))->redirect(route('admin.content-categories.index'));
    }

    public function edit(ContentCategory $contentCategory): View
    {
        return view('admin.content.categories.form', ['category' => $contentCategory]);
    }

    public function update(SaveCategoryRequest $request, ContentCategory $contentCategory): AdminFormResponse
    {
        $this->service->update($contentCategory, CategoryData::fromRequest($request), $this->actor());

        return $this->formResponse(__('content.updated'))->redirect(route('admin.content-categories.index'));
    }

    public function destroy(DestroyContentRequest $request, ContentCategory $contentCategory): AdminFormResponse
    {
        $this->service->delete($contentCategory, $this->actor(), $request->reason());

        return $this->formResponse(__('content.deleted'))->reloadTable(ContentCategoriesDataTable::TABLE_ID);
    }

    public function activity(ContentCategory $contentCategory, AuditTrailService $trail): View
    {
        return view('admin.partials.activity', ['logs' => $trail->latestFor($contentCategory)]);
    }
}
