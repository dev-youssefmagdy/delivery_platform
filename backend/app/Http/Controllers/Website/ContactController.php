<?php

namespace App\Http\Controllers\Website;

use App\Enums\Support\TicketCategory;
use App\Exceptions\BusinessRuleViolation;
use App\Http\Requests\Website\ContactRequest;
use App\Services\Content\ContentReadService;
use App\Services\Support\TicketService;
use App\Support\Http\ApiResponse;
use App\Support\Website\MetaBuilder;
use App\Support\Website\SitePaths;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

/**
 * Contact (SRS 17.3): validated, throttled (`public-forms`), honeypot and time trap; creates a support ticket through the
 * existing support service and shows its reference number.
 */
final class ContactController extends SiteController
{
    public function __construct(
        ContentReadService $content,
        MetaBuilder $meta,
        SitePaths $paths,
        private readonly TicketService $tickets,
    ) {
        parent::__construct($content, $meta, $paths);
    }

    public function show(): View
    {
        $page = $this->content->page('contact', $this->locale());
        $seo = $page['seo'] ?? [];
        $meta = $this->meta->make('contact', [], (string) ($page['title'] ?? __('site.contact.title')), ($page['summary'] ?? null) ?: (string) __('site.contact.description'), $seo, $page['hero_image'] ?? null);

        return $this->page('website.pages.contact', [
            'cmsPage' => $page,
            'categories' => TicketCategory::forWebsite(),
            'reference' => session('ticket_reference'),
        ], $meta);
    }

    public function store(ContactRequest $request): JsonResponse|RedirectResponse
    {
        try {
            $ticket = $this->tickets->openFromWebsite($request->toData());
        } catch (BusinessRuleViolation $e) {
            throw ValidationException::withMessages([$e->field ?? 'email' => $e->getMessage()]);
        }

        $reference = (string) $ticket->number;

        if ($request->expectsJson()) {
            return ApiResponse::success((string) __('site.contact.success_message'), ['reference' => $reference]);
        }

        return redirect()->to($this->paths->path('contact'))->with('ticket_reference', $reference);
    }
}
