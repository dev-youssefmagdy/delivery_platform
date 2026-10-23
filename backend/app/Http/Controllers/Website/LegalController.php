<?php

namespace App\Http\Controllers\Website;

use App\Enums\Policy\PolicyType;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Response;

/**
 * Legal pages from policy_versions (terms, privacy, cancellation, restricted goods, driver terms, coupon rules). The text is
 * PLAIN TEXT: it is escaped and its line breaks are kept (white-space: pre-line); it is never rendered as HTML.
 */
final class LegalController extends SiteController
{
    public function index(): View
    {
        $meta = $this->meta->make('legal-index', [], (string) __('site.legal.title'), (string) __('site.legal.description'));

        return $this->page('website.pages.legal-index', ['documents' => $this->content->legalPages($this->locale())], $meta);
    }

    public function show(string $type): View|Response
    {
        $policyType = PolicyType::tryFrom($type);

        if ($policyType === null) {
            return $this->notFound();
        }

        try {
            $policy = $this->content->policy($policyType, $this->locale());
        } catch (ModelNotFoundException) {
            return $this->notFound();
        }

        $meta = $this->meta->make('legal', ['type' => $type], $policy['title'], (string) __('site.legal.version', ['version' => $policy['version']]));

        return $this->page('website.pages.legal', ['policy' => $policy], $meta);
    }
}
