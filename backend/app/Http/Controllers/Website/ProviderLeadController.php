<?php

namespace App\Http\Controllers\Website;

use App\Http\Requests\Website\ProviderLeadRequest;
use App\Repositories\Contracts\Catalog\ServiceCategoryRepositoryInterface;
use App\Services\Content\ContentReadService;
use App\Services\Lead\LeadService;
use App\Support\Http\ApiResponse;
use App\Support\Website\MetaBuilder;
use App\Support\Website\SitePaths;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

/**
 * "Become a provider" (SRS 17.2, 17.3): the CMS recruitment page plus a lead form that creates an onboarding lead and notifies
 * staff. No documents are accepted here. The same URL answers the validate-before-submit (Precognition) call, the AJAX submit and
 * the plain POST (no JavaScript), all through ProviderLeadRequest.
 */
final class ProviderLeadController extends SiteController
{
    public function __construct(
        ContentReadService $content,
        MetaBuilder $meta,
        SitePaths $paths,
        private readonly LeadService $leads,
        private readonly ServiceCategoryRepositoryInterface $services,
    ) {
        parent::__construct($content, $meta, $paths);
    }

    public function show(): View
    {
        $page = $this->content->page('become-a-driver', $this->locale());
        $seo = $page['seo'] ?? [];
        $meta = $this->meta->make('become-provider', [], (string) ($page['title'] ?? __('site.provider.title')), ($page['summary'] ?? null) ?: (string) __('site.provider.description'), $seo, $page['hero_image'] ?? null);

        return $this->page('website.pages.provider', [
            'cmsPage' => $page,
            'cities' => $this->content->cities($this->locale()),
            'services' => $this->services->publicList(null, null),
            'reference' => session('lead_reference'),
        ], $meta);
    }

    public function store(ProviderLeadRequest $request): JsonResponse|RedirectResponse
    {
        $result = $this->leads->submit($request->data());
        $reference = (string) $result['lead']->reference;

        if ($request->expectsJson()) {
            return ApiResponse::success((string) __('site.provider.success_message'), ['reference' => $reference]);
        }

        return redirect()->to($this->paths->path('become-provider'))->with('lead_reference', $reference);
    }
}
