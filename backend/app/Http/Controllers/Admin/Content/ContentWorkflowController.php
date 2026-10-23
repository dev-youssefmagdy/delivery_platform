<?php

namespace App\Http\Controllers\Admin\Content;

use App\Enums\Common\Locale;
use App\Enums\Content\ContentType;
use App\Enums\Content\WorkflowAction;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\Admin\Content\ContentTransitionRequest;
use App\Http\Requests\Admin\Content\RestoreRevisionRequest;
use App\Services\Content\ContentEntityService;
use App\Services\Content\ContentReadService;
use App\Services\Content\ContentRevisionService;
use App\Services\Content\ContentWorkflowService;
use App\Support\Http\AdminFormResponse;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Workflow steps, preview and revision history, shared by pages, articles, FAQs, banners and menus
 * (/admin/content/{type}/{id}/...).
 */
final class ContentWorkflowController extends AdminController
{
    public function transition(ContentTransitionRequest $request, string $contentType, string $content, string $action, ContentWorkflowService $workflow): AdminFormResponse
    {
        $workflow->transition(
            $request->content(),
            WorkflowAction::from($action),
            $this->actor(),
            $request->reason(),
            $request->scheduledAt(),
        );

        return $this->formResponse(__("admin.content.transitioned.{$action}"))->reload();
    }

    public function preview(Request $request, string $contentType, string $content, ContentReadService $read): View
    {
        $model = $this->resolve($contentType, $content);
        Gate::authorize('view', $model);
        $locale = Locale::tryFromValue((string) $request->query('locale')) ?? Locale::default();

        return view('admin.content.preview', [
            'content' => $model,
            'type' => ContentType::from($contentType),
            'locale' => $locale,
            'payload' => $read->preview($model, $locale->value),
        ]);
    }

    public function revisions(string $contentType, string $content, ContentRevisionService $revisions): View
    {
        $model = $this->resolve($contentType, $content);
        Gate::authorize('view', $model);

        return view('admin.content.partials.revisions', [
            'content' => $model,
            'type' => ContentType::from($contentType),
            'revisions' => $revisions->history($model),
        ]);
    }

    public function restoreRevision(RestoreRevisionRequest $request, string $contentType, string $content, int $number, ContentEntityService $entities): AdminFormResponse
    {
        $entities->restoreRevision($this->resolve($contentType, $content), $number, $this->actor(), $request->reason());

        return $this->formResponse(__('admin.content.revision_restored'))->reload();
    }

    private function resolve(string $contentType, string $id): Model
    {
        return ContentType::from($contentType)->modelClass()::query()->findOrFail((int) $id);
    }
}
