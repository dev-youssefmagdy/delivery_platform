<?php

namespace App\Http\Controllers\Admin\Content;

use App\DataTables\Admin\Content\ContentMediaDataTable;
use App\DTOs\Content\MediaUploadData;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\Admin\Content\DestroyContentRequest;
use App\Http\Requests\Admin\Content\StoreMediaRequest;
use App\Http\Requests\Admin\Content\UpdateMediaRequest;
use App\Models\ContentMedia;
use App\Services\Audit\AuditTrailService;
use App\Services\Content\ContentMediaService;
use App\Support\Http\AdminFormResponse;
use Illuminate\Contracts\View\View;

/**
 * Media library (content.manage): validated, re-encoded and resized image uploads on the public disk.
 */
final class MediaController extends AdminController
{
    public function __construct(private readonly ContentMediaService $service) {}

    public function index(ContentMediaDataTable $table): mixed
    {
        return $table->render('admin.content.media.index');
    }

    public function create(): View
    {
        return view('admin.content.media.create');
    }

    public function store(StoreMediaRequest $request): AdminFormResponse
    {
        $this->service->store(MediaUploadData::fromRequest($request), $this->actor());

        return $this->formResponse(__('admin.content.media_uploaded'))->redirect(route('admin.content-media.index'));
    }

    public function edit(ContentMedia $contentMedia): View
    {
        return view('admin.content.media.edit', ['media' => $contentMedia]);
    }

    public function update(UpdateMediaRequest $request, ContentMedia $contentMedia): AdminFormResponse
    {
        /** @var array<string, string|null> $alt */
        $alt = (array) $request->validated('alt');
        $this->service->updateAlt($contentMedia, array_filter(array_map(static fn (?string $value): ?string => $value !== null && trim($value) !== '' ? trim($value) : null, $alt)), $this->actor());

        return $this->formResponse(__('admin.content.updated'))->redirect(route('admin.content-media.index'));
    }

    public function destroy(DestroyContentRequest $request, ContentMedia $contentMedia): AdminFormResponse
    {
        $this->service->delete($contentMedia, $this->actor(), $request->reason());

        return $this->formResponse(__('admin.content.deleted'))->reloadTable(ContentMediaDataTable::TABLE_ID);
    }

    public function activity(ContentMedia $contentMedia, AuditTrailService $trail): View
    {
        return view('admin.partials.activity', ['logs' => $trail->latestFor($contentMedia)]);
    }
}
