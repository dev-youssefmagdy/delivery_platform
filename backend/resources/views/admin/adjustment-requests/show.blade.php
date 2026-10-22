@php
    $tz = (string) config('admin.display_timezone', 'UTC');
    $when = static fn (?\Carbon\CarbonInterface $at): ?string => $at?->copy()->setTimezone($tz)->translatedFormat('Y-m-d H:i:s');
    $owner = $adjustment->account->owner;
    $user = $owner instanceof \App\Models\ProviderProfile ? $owner->user : ($owner instanceof \App\Models\User ? $owner : null);
@endphp
<x-admin.layouts.app :title="__('admin.adjustments.show_title')">
    <x-admin.page-header
        :title="__('admin.adjustments.show_title').' '.\Illuminate\Support\Str::substr($adjustment->uuid, 0, 8)"
        :subtitle="$adjustment->uuid"
        :breadcrumbs="[['label' => __('admin.adjustments.title'), 'url' => route('admin.adjustment-requests.index')], ['label' => \Illuminate\Support\Str::substr($adjustment->uuid, 0, 8)]]"
    >
        @can('approve', $adjustment)
            <x-admin.confirm-action :url="route('admin.adjustment-requests.approve', $adjustment)" :title="__('admin.adjustments.confirm.approve_title')" :message="__('admin.adjustments.confirm.approve_message')" :confirm-label="__('admin.adjustments.actions.approve')" variant="success" icon="check">{{ __('admin.adjustments.actions.approve') }}</x-admin.confirm-action>
        @endcan
        @can('reject', $adjustment)
            <x-admin.confirm-action :url="route('admin.adjustment-requests.reject', $adjustment)" :title="__('admin.adjustments.confirm.reject_title')" :message="__('admin.adjustments.confirm.reject_message')" :confirm-label="__('admin.adjustments.actions.reject')" reason-required icon="x-mark">{{ __('admin.adjustments.actions.reject') }}</x-admin.confirm-action>
        @endcan
        @can('cancel', $adjustment)
            <x-admin.confirm-action :url="route('admin.adjustment-requests.cancel', $adjustment)" :title="__('admin.adjustments.confirm.cancel_title')" :message="__('admin.adjustments.confirm.cancel_message')" :confirm-label="__('admin.adjustments.actions.cancel')" reason-required variant="warning" icon="x-mark">{{ __('admin.adjustments.actions.cancel') }}</x-admin.confirm-action>
        @endcan
    </x-admin.page-header>

    @if ($adjustment->status === \App\Enums\Finance\ApprovalStatus::Pending)
        <x-admin.alert type="warning" class="mb-6" :title="__('admin.adjustments.notices.pending_title')">{{ __('admin.adjustments.notices.pending') }}</x-admin.alert>
    @endif

    <x-admin.card class="mb-6">
        <x-admin.detail-list>
            <x-admin.detail-list.item :label="__('admin.adjustments.fields.status')"><x-admin.badge :value="$adjustment->status" /></x-admin.detail-list.item>
            <x-admin.detail-list.item :label="__('admin.adjustments.fields.direction')"><x-admin.badge :value="$adjustment->direction" /></x-admin.detail-list.item>
            <x-admin.detail-list.item :label="__('admin.adjustments.fields.amount')"><x-admin.money :minor="$adjustment->amount_minor" :currency="$adjustment->currency" /></x-admin.detail-list.item>
            <x-admin.detail-list.item :label="__('admin.adjustments.fields.wallet')">
                @can('view', $adjustment->account)
                    <a href="{{ route('admin.ledger-accounts.show', $adjustment->account) }}" class="text-brand-700 hover:underline">{{ ($user?->name ?? __('admin.ledger.accounts.unnamed')).' · '.$adjustment->account->type->label() }}</a>
                @else
                    {{ ($user?->name ?? __('admin.ledger.accounts.unnamed')).' · '.$adjustment->account->type->label() }}
                @endcan
            </x-admin.detail-list.item>
            <x-admin.detail-list.item :label="__('admin.adjustments.fields.reference')"><span class="ltr-only">{{ $adjustment->reference ?? '—' }}</span></x-admin.detail-list.item>
            <x-admin.detail-list.item :label="__('admin.adjustments.fields.requested_by')" :value="$adjustment->requestedBy?->name" />
            <x-admin.detail-list.item :label="__('admin.adjustments.fields.created')" :value="$when($adjustment->created_at)" />
            <x-admin.detail-list.item :label="__('admin.adjustments.fields.decided_by')" :value="$adjustment->decidedBy?->name" />
            <x-admin.detail-list.item :label="__('admin.adjustments.fields.decided_at')" :value="$when($adjustment->decided_at)" />
            <x-admin.detail-list.item :label="__('admin.common.reason')" :value="$adjustment->reason" full />
            @if ($adjustment->decision_reason)
                <x-admin.detail-list.item :label="__('admin.adjustments.fields.decision_reason')" :value="$adjustment->decision_reason" full />
            @endif
            <x-admin.detail-list.item :label="__('admin.adjustments.fields.ledger')">
                @if ($adjustment->journal_entry_id)
                    @can('viewAny', \App\Models\JournalEntry::class)
                        <a href="{{ route('admin.journal-entries.index', ['filters' => ['search' => 'adjustment:'.$adjustment->uuid]]) }}" class="text-brand-700 hover:underline">{{ __('admin.adjustments.ledger_posted') }}</a>
                    @else
                        {{ __('admin.adjustments.ledger_posted') }}
                    @endcan
                @else
                    {{ __('admin.adjustments.ledger_not_posted') }}
                @endif
            </x-admin.detail-list.item>
        </x-admin.detail-list>
    </x-admin.card>

    <x-admin.card :padding="false" class="p-5">
        <h3 class="mb-4 text-sm font-semibold text-slate-800">{{ __('admin.common.activity') }}</h3>
        @include('admin.partials.activity', ['logs' => $logs])
    </x-admin.card>
</x-admin.layouts.app>
