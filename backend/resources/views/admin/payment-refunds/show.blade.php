@php
    $tz = (string) config('admin.display_timezone', 'UTC');
    $when = static fn (?\Carbon\CarbonInterface $at): ?string => $at?->copy()->setTimezone($tz)->translatedFormat('Y-m-d H:i:s');
@endphp
<x-admin.layouts.app :title="__('admin.refunds.show_title')">
    <x-admin.page-header
        :title="__('admin.refunds.show_title').' '.\Illuminate\Support\Str::substr($refund->uuid, 0, 8)"
        :subtitle="$refund->uuid"
        :breadcrumbs="[['label' => __('admin.refunds.title'), 'url' => route('admin.payment-refunds.index')], ['label' => \Illuminate\Support\Str::substr($refund->uuid, 0, 8)]]"
    >
        @can('approve', $refund)
            <x-admin.confirm-action :url="route('admin.payment-refunds.approve', $refund)" :title="__('admin.refunds.confirm.approve_title')" :message="__('admin.refunds.confirm.approve_message')" :confirm-label="__('admin.refunds.actions.approve')" variant="success" icon="check">{{ __('admin.refunds.actions.approve') }}</x-admin.confirm-action>
        @endcan
        @can('reject', $refund)
            <x-admin.confirm-action :url="route('admin.payment-refunds.reject', $refund)" :title="__('admin.refunds.confirm.reject_title')" :message="__('admin.refunds.confirm.reject_message')" :confirm-label="__('admin.refunds.actions.reject')" reason-required icon="x-mark">{{ __('admin.refunds.actions.reject') }}</x-admin.confirm-action>
        @endcan
    </x-admin.page-header>

    @if ($refund->status === \App\Enums\Payments\RefundStatus::PendingApproval)
        <x-admin.alert type="warning" class="mb-6" :title="__('admin.refunds.notices.pending_title')">{{ __('admin.refunds.notices.pending') }}</x-admin.alert>
    @elseif ($refund->status === \App\Enums\Payments\RefundStatus::Processing)
        <x-admin.alert type="info" class="mb-6">{{ __('admin.refunds.notices.processing') }}</x-admin.alert>
    @elseif ($refund->status === \App\Enums\Payments\RefundStatus::Failed)
        <x-admin.alert type="danger" class="mb-6">{{ __('admin.refunds.notices.failed', ['code' => $refund->failure_code]) }}</x-admin.alert>
    @endif

    <x-admin.card class="mb-6">
        <x-admin.detail-list>
            <x-admin.detail-list.item :label="__('admin.refunds.fields.status')"><x-admin.badge :value="$refund->status" /></x-admin.detail-list.item>
            <x-admin.detail-list.item :label="__('admin.refunds.fields.amount')"><x-admin.money :minor="$refund->amount_minor" :currency="$refund->currency" /></x-admin.detail-list.item>
            <x-admin.detail-list.item :label="__('admin.refunds.fields.dual')" :value="$refund->requires_dual_approval ? __('admin.common.yes') : __('admin.common.no')" />
            <x-admin.detail-list.item :label="__('admin.refunds.fields.payment')">
                @can('view', $refund->payment)
                    <a href="{{ route('admin.payments.show', $refund->payment) }}" class="ltr-only text-brand-700 hover:underline">{{ \Illuminate\Support\Str::substr($refund->payment->uuid, 0, 8) }}</a>
                @endcan
            </x-admin.detail-list.item>
            <x-admin.detail-list.item :label="__('admin.refunds.fields.booking')"><span class="ltr-only">{{ $refund->payment->booking->code }}</span></x-admin.detail-list.item>
            <x-admin.detail-list.item :label="__('admin.refunds.fields.requested_by')" :value="$refund->requestedBy?->name" />
            <x-admin.detail-list.item :label="__('admin.refunds.fields.decided_by')" :value="$refund->decidedBy?->name" />
            <x-admin.detail-list.item :label="__('admin.refunds.fields.created')" :value="$when($refund->created_at)" />
            <x-admin.detail-list.item :label="__('admin.refunds.fields.decided_at')" :value="$when($refund->decided_at)" />
            <x-admin.detail-list.item :label="__('admin.refunds.fields.completed_at')" :value="$when($refund->completed_at)" />
            <x-admin.detail-list.item :label="__('admin.refunds.fields.reason')" :value="$refund->reason" full />
            @if ($refund->decision_reason)
                <x-admin.detail-list.item :label="__('admin.refunds.fields.decision_reason')" :value="$refund->decision_reason" full />
            @endif
            <x-admin.detail-list.item :label="__('admin.refunds.fields.ledger')">
                @if ($refund->journal_entry_id)
                    @can('viewAny', \App\Models\JournalEntry::class)
                        <a href="{{ route('admin.journal-entries.index', ['filters' => ['search' => 'refund:'.$refund->uuid]]) }}" class="text-brand-700 hover:underline">{{ __('admin.refunds.ledger_posted') }}</a>
                    @else
                        {{ __('admin.refunds.ledger_posted') }}
                    @endcan
                @else
                    {{ __('admin.refunds.ledger_not_posted') }}
                @endif
            </x-admin.detail-list.item>
        </x-admin.detail-list>
    </x-admin.card>

    <x-admin.card :padding="false" class="p-5">
        <h3 class="mb-4 text-sm font-semibold text-slate-800">{{ __('admin.common.activity') }}</h3>
        @include('admin.partials.activity', ['logs' => $logs])
    </x-admin.card>
</x-admin.layouts.app>
