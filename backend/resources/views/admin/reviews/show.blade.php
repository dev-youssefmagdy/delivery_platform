@php
    $tz = (string) config('admin.display_timezone', 'UTC');
    $when = static fn (?\Carbon\CarbonInterface $at): ?string => $at?->copy()->setTimezone($tz)->translatedFormat('Y-m-d H:i');
    $party = static fn (string $type): string => __('reputation.enums.subject.'.$type);
    $booking = $rating->booking;
    $history = $actions->map(fn ($action): array => [
        'title' => $action->action->label(),
        'badge' => $action->action,
        'time' => $when($action->created_at),
        'actor' => $action->admin?->name ?? __('reputation.admin.system'),
        'description' => $action->from_status->label().' → '.$action->to_status->label(),
        'reason' => $action->reason,
    ])->values()->all();
@endphp
<x-admin.layouts.app :title="__('reputation.admin.reviews.show_title')">
    <x-admin.page-header
        :title="__('reputation.admin.reviews.show_title').' · '.($booking?->code ?? '')"
        :subtitle="$rating->uuid"
        :breadcrumbs="[['label' => __('reputation.admin.reviews.title'), 'url' => route('admin.reviews.index')], ['label' => $booking?->code ?? $rating->uuid]]"
    >
        @can('markUnderReview', $rating)
            <x-admin.button variant="secondary" icon="clock" data-modal-open="review-under-review">{{ __('reputation.admin.actions.under_review') }}</x-admin.button>
        @endcan
        @can('hide', $rating)
            <x-admin.button variant="danger" icon="x-circle" data-modal-open="review-hide">{{ __('reputation.admin.actions.hide') }}</x-admin.button>
        @endcan
        @can('restore', $rating)
            <x-admin.button variant="success" icon="check-circle" data-modal-open="review-restore">{{ __('reputation.admin.actions.restore') }}</x-admin.button>
        @endcan
    </x-admin.page-header>

    @if ($rating->moderation_status !== \App\Enums\Reviews\ModerationStatus::Visible)
        <x-admin.alert type="warning" class="mb-6">{{ __('reputation.admin.reviews.not_counted') }}</x-admin.alert>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-admin.card :title="__('reputation.admin.reviews.the_review')" :subtitle="__('reputation.admin.reviews.read_only')">
                <p class="text-xl text-warning-600" aria-label="{{ __('admin.ratings.stars_label', ['count' => $rating->stars]) }}">{{ str_repeat('★', $rating->stars).str_repeat('☆', 5 - $rating->stars) }}</p>
                @if (! empty($rating->tags))
                    <p class="mt-2 flex flex-wrap gap-1.5">
                        @foreach ((array) $rating->tags as $tag)
                            <x-admin.badge :label="\App\Enums\Reviews\RatingTag::tryFrom($tag)?->label() ?? $tag" color="info" :dot="false" />
                        @endforeach
                    </p>
                @endif
                <p class="mt-3 text-sm whitespace-pre-line text-slate-800">{{ $rating->comment ?: '—' }}</p>
            </x-admin.card>

            <x-admin.card :title="__('reputation.admin.reviews.history')">
                <x-admin.timeline :items="$history" />
            </x-admin.card>

            <x-admin.card :title="__('reputation.admin.tabs.reports')">
                @if ($reports->isEmpty())
                    <x-admin.empty-state icon="exclamation" :title="__('reputation.admin.reviews.no_reports')" compact />
                @else
                    <ul class="divide-y divide-slate-100">
                        @foreach ($reports as $report)
                            <li class="py-3">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <p class="text-sm font-medium text-slate-900">{{ $report->reason->label() }} · {{ $party($report->reporter_type) }}</p>
                                    <x-admin.badge :value="$report->status" />
                                </div>
                                <p class="mt-0.5 text-xs text-slate-500">{{ $when($report->created_at) }}@if ($report->handledBy) · {{ $report->handledBy->name }}@endif</p>
                                @if ($report->details)
                                    <p class="mt-1 text-sm text-slate-700">{{ $report->details }}</p>
                                @endif
                                @if ($report->resolution_note)
                                    <p class="mt-1 text-xs text-slate-500">{{ __('reputation.admin.reviews.note', ['note' => $report->resolution_note]) }}</p>
                                @endif
                                @can('dismiss', $report)
                                    <div class="mt-2">
                                        <x-admin.confirm-action :url="route('admin.reviews.reports.dismiss', ['rating' => $rating, 'report' => $report])" :title="__('reputation.admin.confirm.dismiss_report_title')" :message="__('reputation.admin.confirm.dismiss_report_message')" :confirm-label="__('reputation.admin.actions.dismiss_report')" variant="secondary" reason-required icon="x-mark">{{ __('reputation.admin.actions.dismiss_report') }}</x-admin.confirm-action>
                                    </div>
                                @endcan
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-admin.card>

            <x-admin.card :title="__('reputation.admin.tabs.appeals')">
                @if ($appeals->isEmpty())
                    <x-admin.empty-state icon="clipboard" :title="__('reputation.admin.reviews.no_appeals')" compact />
                @else
                    <ul class="divide-y divide-slate-100">
                        @foreach ($appeals as $appeal)
                            <li class="py-3">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <p class="text-sm font-medium text-slate-900">{{ $appeal->grounds->label() }} · {{ $party($appeal->appellant_type) }}</p>
                                    <x-admin.badge :value="$appeal->status" />
                                </div>
                                <p class="mt-0.5 text-xs text-slate-500">{{ $when($appeal->created_at) }}@if ($appeal->decidedBy) · {{ $appeal->decidedBy->name }}@endif</p>
                                @if ($appeal->message !== '')
                                    <p class="mt-1 text-sm text-slate-700">{{ $appeal->message }}</p>
                                @endif
                                @if ($appeal->decision_reason)
                                    <p class="mt-1 text-xs text-slate-500">{{ __('reputation.admin.reviews.note', ['note' => $appeal->decision_reason]) }}</p>
                                @endif
                                @can('decide', $appeal)
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        <x-admin.confirm-action :url="route('admin.reviews.appeals.decide', ['rating' => $rating, 'appeal' => $appeal, 'decision' => 'uphold'])" :title="__('reputation.admin.confirm.uphold_title')" :message="__('reputation.admin.confirm.uphold_message')" :confirm-label="__('reputation.admin.actions.uphold')" variant="success" reason-required icon="check-circle">{{ __('reputation.admin.actions.uphold') }}</x-admin.confirm-action>
                                        <x-admin.confirm-action :url="route('admin.reviews.appeals.decide', ['rating' => $rating, 'appeal' => $appeal, 'decision' => 'reject'])" :title="__('reputation.admin.confirm.reject_title')" :message="__('reputation.admin.confirm.reject_message')" :confirm-label="__('reputation.admin.actions.reject')" variant="secondary" reason-required icon="x-mark">{{ __('reputation.admin.actions.reject') }}</x-admin.confirm-action>
                                    </div>
                                @endcan
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-admin.card>
        </div>

        <div class="space-y-6">
            <x-admin.card :title="__('admin.common.details')">
                <x-admin.detail-list :cols="1">
                    <x-admin.detail-list.item :label="__('admin.ratings.fields.moderation')"><x-admin.badge :label="$rating->moderation_status->label()" :color="match ($rating->moderation_status) { \App\Enums\Reviews\ModerationStatus::Visible => 'success', \App\Enums\Reviews\ModerationStatus::Hidden => 'danger', default => 'warning' }" /></x-admin.detail-list.item>
                    <x-admin.detail-list.item :label="__('admin.bookings.fields.code')">
                        @if ($booking)
                            @can('view', $booking)
                                <a class="font-medium text-brand-700 hover:underline" href="{{ route('admin.bookings.show', $booking->uuid) }}">{{ $booking->code }}</a>
                            @else
                                {{ $booking->code }}
                            @endcan
                        @endif
                    </x-admin.detail-list.item>
                    <x-admin.detail-list.item :label="__('reputation.admin.fields.rater')" :value="($raterName ?? '—').' · '.$party($rating->rater_type)" />
                    <x-admin.detail-list.item :label="__('reputation.admin.fields.ratee')" :value="($rateeName ?? '—').' · '.$party($rating->ratee_type)" />
                    <x-admin.detail-list.item :label="__('reputation.admin.fields.reports')" :value="(string) $rating->reports_count" />
                    <x-admin.detail-list.item :label="__('admin.ratings.fields.created')" :value="$when($rating->created_at)" />
                    <x-admin.detail-list.item :label="__('reputation.admin.fields.moderated_at')" :value="$when($rating->moderated_at)" />
                </x-admin.detail-list>
            </x-admin.card>
        </div>
    </div>

    @can('hide', $rating)
        <x-admin.action-modal id="review-hide" :title="__('reputation.admin.confirm.hide_title')" :message="__('reputation.admin.confirm.hide_message')" :action="route('admin.reviews.hide', $rating)" :submit="__('reputation.admin.actions.hide')" icon="x-circle" />
    @endcan
    @can('restore', $rating)
        <x-admin.action-modal id="review-restore" :title="__('reputation.admin.confirm.restore_title')" :message="__('reputation.admin.confirm.restore_message')" :action="route('admin.reviews.restore', $rating)" :submit="__('reputation.admin.actions.restore')" variant="success" icon="check-circle" />
    @endcan
    @can('markUnderReview', $rating)
        <x-admin.action-modal id="review-under-review" :title="__('reputation.admin.confirm.under_review_title')" :message="__('reputation.admin.confirm.under_review_message')" :action="route('admin.reviews.under-review', $rating)" :submit="__('reputation.admin.actions.under_review')" variant="warning" icon="clock" />
    @endcan
</x-admin.layouts.app>
