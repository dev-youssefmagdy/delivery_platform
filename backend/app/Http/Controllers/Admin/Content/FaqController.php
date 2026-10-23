<?php

namespace App\Http\Controllers\Admin\Content;

use App\DataTables\Admin\Content\ContentFaqsDataTable;
use App\DTOs\Content\FaqData;
use App\Enums\Content\ContentType;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\Admin\Content\DestroyContentRequest;
use App\Http\Requests\Admin\Content\SaveFaqRequest;
use App\Models\ContentFaq;
use App\Services\Audit\AuditTrailService;
use App\Services\Content\ContentEntityService;
use App\Support\Http\AdminFormResponse;
use Illuminate\Contracts\View\View;

/**
 * CMS faqs (content.manage writes drafts, content.publish moves them through the workflow).
 */
final class FaqController extends AdminController
{
    public function __construct(private readonly ContentEntityService $service) {}

    public function index(ContentFaqsDataTable $table): mixed
    {
        return $table->render('admin.content.faqs.index');
    }

    public function create(): View
    {
        return view('admin.content.faqs.form', ['faq' => null]);
    }

    public function store(SaveFaqRequest $request): AdminFormResponse
    {
        $contentFaq = $this->service->create(ContentType::Faq, FaqData::fromRequest($request), $this->actor());

        return $this->formResponse(__('content.created'))->redirect(route('admin.content-faqs.show', $contentFaq));
    }

    public function show(ContentFaq $contentFaq): View
    {
        return view('admin.content.faqs.show', ['faq' => $contentFaq->loadMissing(['publisher:id,name', 'creator:id,name', 'updater:id,name', 'category', 'serviceCategory'])]);
    }

    public function activity(ContentFaq $contentFaq, AuditTrailService $trail): View
    {
        return view('admin.partials.activity', ['logs' => $trail->latestFor($contentFaq)]);
    }

    public function edit(ContentFaq $contentFaq): View
    {
        return view('admin.content.faqs.form', ['faq' => $contentFaq->loadMissing(['category', 'serviceCategory'])]);
    }

    public function update(SaveFaqRequest $request, ContentFaq $contentFaq): AdminFormResponse
    {
        $this->service->update($contentFaq, FaqData::fromRequest($request), $this->actor());

        return $this->formResponse(__('content.updated'))->redirect(route('admin.content-faqs.show', $contentFaq));
    }

    public function destroy(DestroyContentRequest $request, ContentFaq $contentFaq): AdminFormResponse
    {
        $this->service->delete($contentFaq, $this->actor(), $request->reason());

        $response = $this->formResponse(__('content.deleted'))->reloadTable(ContentFaqsDataTable::TABLE_ID);

        return $request->boolean('from_show') ? $response->redirect(route('admin.content-faqs.index')) : $response;
    }
}
