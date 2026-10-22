@php
    $tz = (string) config('admin.display_timezone', 'UTC');
    $when = static fn (?\Carbon\CarbonInterface $at): ?string => $at?->copy()->setTimezone($tz)->translatedFormat('Y-m-d H:i:s');
    $money = static fn (int $minor): \App\Support\Money\Money => \App\Support\Money\Money::of($minor, app(\App\Support\Money\CurrencyRegistry::class)->get($payout->currency));
    $items = $events->map(static fn (\App\Models\PayoutEvent $event): array => [
        'title' => $event->to_status->label(),
        'badge' => $event->to_status,
        'time' => $when($event->created_at),
        'actor' => __('admin.payouts.actors.'.$event->actor_type),
        'description' => $event->note,
    ])->all();
@endphp
<x-admin.layouts.app :title="__('admin.payouts.show_title')">
    <x-admin.page-header
        :title="__('admin.payouts.show_title').' '.\Illuminate\Support\Str::substr($payout->uuid, 0, 8)"
        :subtitle="$payout->uuid"
        :breadcrumbs="[['label' => __('admin.payouts.title'), 'url' => route('admin.payout-requests.index')], ['label' => \Illuminate\Support\Str::substr($payout->uuid, 0, 8)]]"
    >
        @can('review', $payout)
            <x-admin.confirm-action :url="route('admin.payout-requests.review', $payout)" :title="__('admin.payouts.confirm.review_title')" :message="__('admin.payouts.confirm.review_message')" :confirm-label="__('admin.payouts.actions.review')" variant="primary" icon="eye">{{ __('admin.payouts.actions.review') }}</x-admin.confirm-action>
        @endcan
        @can('approve', $payout)
            <x-admin.confirm-action :url="route('admin.payout-requests.approve', $payout)" :title="__('admin.payouts.confirm.approve_title')" :message="__('admin.payouts.confirm.approve_message')" :confirm-label="__('admin.payouts.actions.approve')" variant="success" icon="check">{{ __('admin.payouts.actions.approve') }}</x-admin.confirm-action>
        @endcan
        @can('reject', $payout)
            <x-admin.confirm-action :url="route('admin.payout-requests.reject', $payout)" :title="__('admin.payouts.confirm.reject_title')" :message="__('admin.payouts.confirm.reject_message')" :confirm-label="__('admin.payouts.actions.reject')" reason-required icon="x-mark">{{ __('admin.payouts.actions.reject') }}</x-admin.confirm-action>
        @endcan
        @can('markProcessing', $payout)
            <x-admin.confirm-action :url="route('admin.payout-requests.processing', $payout)" :title="__('admin.payouts.confirm.processing_title')" :message="__('admin.payouts.confirm.processing_message')" :confirm-label="__('admin.payouts.actions.processing')" variant="primary" icon="play">{{ __('admin.payouts.actions.processing') }}</x-admin.confirm-action>
        @endcan
        @can('markPaid', $payout)
            <x-admin.button variant="success" icon="check-circle" data-modal-open="payout-paid">{{ __('admin.payouts.actions.paid') }}</x-admin.button>
            <x-admin.confirm-action :url="route('admin.payout-requests.failed', $payout)" :title="__('admin.payouts.confirm.failed_title')" :message="__('admin.payouts.confirm.failed_message')" :confirm-label="__('admin.payouts.actions.failed')" reason-required icon="x-mark">{{ __('admin.payouts.actions.failed') }}</x-admin.confirm-action>
        @endcan
        @can('reverse', $payout)
            <x-admin.confirm-action :url="route('admin.payout-requests.reverse', $payout)" :title="__('admin.payouts.confirm.reverse_title')" :message="__('admin.payouts.confirm.reverse_message')" :confirm-label="__('admin.payouts.actions.reverse')" reason-required icon="refresh">{{ __('admin.payouts.actions.reverse') }}</x-admin.confirm-action>
        @endcan
    </x-admin.page-header>

    @if ($payout->status === \App\Enums\Payout\PayoutStatus::UnderReview && $payout->requires_dual_approval && $payout->first_approved_by_id)
        <x-admin.alert type="warning" class="mb-6" :title="__('admin.payouts.notices.second_title')">{{ __('admin.payouts.notices.second', ['name' => $payout->firstApprovedBy?->name]) }}</x-admin.alert>
    @elseif ($payout->requires_dual_approval && in_array($payout->status, \App\Enums\Payout\PayoutStatus::awaitingDecision(), true))
        <x-admin.alert type="info" class="mb-6">{{ __('admin.payouts.notices.dual') }}</x-admin.alert>
    @endif
    @if ($payout->status === \App\Enums\Payout\PayoutStatus::Failed || $payout->status === \App\Enums\Payout\PayoutStatus::Reversed)
        <x-admin.alert type="danger" class="mb-6">{{ __('admin.payouts.notices.returned', ['reason' => $payout->failure_reason]) }}</x-admin.alert>
    @endif

    <x-admin.card class="mb-6">
        <x-admin.detail-list>
            <x-admin.detail-list.item :label="__('admin.payouts.fields.status')"><x-admin.badge :value="$payout->status" /></x-admin.detail-list.item>
            <x-admin.detail-list.item :label="__('admin.payouts.fields.provider')">
                @can('view', $payout->provider)
                    <a href="{{ route('admin.providers.show', $payout->provider) }}" class="text-brand-700 hover:underline">{{ $payout->provider->user->name ?? __('admin.providers.unnamed') }}</a>
                @else
                    {{ $payout->provider->user->name ?? __('admin.providers.unnamed') }}
                @endcan
            </x-admin.detail-list.item>
            <x-admin.detail-list.item :label="__('admin.payouts.fields.amount')"><x-admin.money :minor="$payout->amount_minor" :currency="$payout->currency" /></x-admin.detail-list.item>
            <x-admin.detail-list.item :label="__('admin.payouts.fields.fee')"><x-admin.money :minor="$payout->fee_minor" :currency="$payout->currency" /></x-admin.detail-list.item>
            <x-admin.detail-list.item :label="__('admin.payouts.fields.net')"><x-admin.money :minor="$payout->net_minor" :currency="$payout->currency" /></x-admin.detail-list.item>
            <x-admin.detail-list.item :label="__('admin.payouts.fields.dual')" :value="$payout->requires_dual_approval ? __('admin.common.yes') : __('admin.common.no')" />
            <x-admin.detail-list.item :label="__('admin.payouts.fields.method')" :value="($payout->method_snapshot['label'] ?? '—').' · '.\App\Enums\Payout\PayoutMethodType::tryFrom((string) ($payout->method_snapshot['type'] ?? ''))?->label().(($payout->method_snapshot['last4'] ?? null) ? ' •••• '.$payout->method_snapshot['last4'] : '')" />
            <x-admin.detail-list.item :label="__('admin.payouts.fields.batch')">
                @if ($payout->batch)
                    <a href="{{ route('admin.payout-batches.show', $payout->batch) }}" class="ltr-only text-brand-700 hover:underline">{{ $payout->batch->reference }}</a>
                @else
                    —
                @endif
            </x-admin.detail-list.item>
            <x-admin.detail-list.item :label="__('admin.payouts.fields.requested_at')" :value="$when($payout->created_at)" />
            <x-admin.detail-list.item :label="__('admin.payouts.fields.reviewed_by')" :value="$payout->reviewedBy?->name" />
            <x-admin.detail-list.item :label="__('admin.payouts.fields.first_approver')" :value="$payout->firstApprovedBy?->name" />
            <x-admin.detail-list.item :label="__('admin.payouts.fields.approver')" :value="$payout->approvedBy?->name" />
            <x-admin.detail-list.item :label="__('admin.payouts.fields.approved_at')" :value="$when($payout->approved_at)" />
            <x-admin.detail-list.item :label="__('admin.payouts.fields.transfer_reference')"><span class="ltr-only break-all">{{ $payout->transfer_reference ?? '—' }}</span></x-admin.detail-list.item>
            <x-admin.detail-list.item :label="__('admin.payouts.fields.paid_at')" :value="$when($payout->paid_at)" />
            <x-admin.detail-list.item :label="__('admin.payouts.fields.evidence')">
                @if ($payout->evidence_path)
                    <a href="{{ route('admin.payout-requests.evidence', $payout) }}" class="text-brand-700 hover:underline">{{ __('admin.payouts.evidence_download') }}</a>
                @else
                    —
                @endif
            </x-admin.detail-list.item>
            @if ($payout->rejection_reason)
                <x-admin.detail-list.item :label="__('admin.payouts.fields.rejection_reason')" :value="$payout->rejection_reason" full />
            @endif
            @if ($payout->decision_note)
                <x-admin.detail-list.item :label="__('admin.payouts.fields.decision_note')" :value="$payout->decision_note" full />
            @endif
        </x-admin.detail-list>
    </x-admin.card>

    <x-admin.card class="mb-6">
        <h3 class="mb-4 text-sm font-semibold text-slate-800">{{ __('admin.payouts.timeline') }}</h3>
        <x-admin.timeline :items="$items" />
    </x-admin.card>

    <x-admin.card :padding="false" class="p-5">
        <h3 class="mb-4 text-sm font-semibold text-slate-800">{{ __('admin.common.activity') }}</h3>
        @include('admin.partials.activity', ['logs' => $logs])
    </x-admin.card>

    @can('markPaid', $payout)
        <x-admin.action-modal id="payout-paid" :title="__('admin.payouts.paid_form.title')" :message="__('admin.payouts.paid_form.message', ['net' => app(\App\Support\Money\MoneyFormatter::class)->format($money($payout->net_minor))])" :action="route('admin.payout-requests.paid', $payout)" :submit="__('admin.payouts.actions.paid')" variant="success" icon="check-circle" :reason-hint="__('admin.payouts.paid_form.note_hint')">
            <x-admin.form.input name="transfer_reference" :label="__('admin.payouts.fields.transfer_reference')" required maxlength="120" dir="ltr" />
            <x-admin.form.file name="evidence" :label="__('admin.payouts.fields.evidence')" accept="application/pdf,image/jpeg,image/png" :max-kb="5120" />
        </x-admin.action-modal>
    @endcan
</x-admin.layouts.app>
