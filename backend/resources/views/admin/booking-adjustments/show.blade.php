@php
    $tz = (string) config('admin.display_timezone', 'UTC');
    $when = static fn (?\Carbon\CarbonInterface $at): ?string => $at?->copy()->setTimezone($tz)->translatedFormat('Y-m-d H:i:s');
@endphp
<x-admin.layouts.app :title="__('admin.booking_adjustments.show_title')">
    <x-admin.page-header
        :title="__('admin.booking_adjustments.show_title').' '.\Illuminate\Support\Str::substr($adjustment->uuid, 0, 8)"
        :subtitle="$adjustment->uuid"
        :breadcrumbs="[['label' => __('admin.booking_adjustments.title'), 'url' => route('admin.booking-adjustments.index')], ['label' => \Illuminate\Support\Str::substr($adjustment->uuid, 0, 8)]]"
    >
        @can('void', $adjustment)
            <x-admin.confirm-action :url="route('admin.booking-adjustments.void', $adjustment->uuid)" :title="__('admin.booking_adjustments.confirm.void_title')" :message="__('admin.booking_adjustments.confirm.void_message')" :confirm-label="__('admin.booking_adjustments.actions.void')" reason-required variant="warning" icon="x-mark">{{ __('admin.booking_adjustments.actions.void') }}</x-admin.confirm-action>
        @endcan
    </x-admin.page-header>

    <x-admin.card class="mb-6">
        <x-admin.detail-list>
            <x-admin.detail-list.item :label="__('admin.booking_adjustments.fields.status')"><x-admin.badge :value="$adjustment->status" /></x-admin.detail-list.item>
            <x-admin.detail-list.item :label="__('admin.booking_adjustments.fields.type')" :value="$adjustment->type->label()" />
            <x-admin.detail-list.item :label="__('admin.booking_adjustments.fields.booking')"><a href="{{ route('admin.bookings.show', $adjustment->booking->uuid) }}" class="ltr-only text-brand-700 hover:underline">{{ $adjustment->booking->code }}</a></x-admin.detail-list.item>
            <x-admin.detail-list.item :label="__('admin.booking_adjustments.fields.requester')" :value="\App\Enums\Common\ActorType::tryFrom($adjustment->requester_type)?->label()" />
            <x-admin.detail-list.item :label="__('admin.booking_adjustments.fields.amount')"><x-admin.money :minor="$adjustment->amount_minor" :currency="$adjustment->currency" /></x-admin.detail-list.item>
            <x-admin.detail-list.item :label="__('admin.booking_adjustments.fields.old_total')"><x-admin.money :minor="$adjustment->old_total_minor" :currency="$adjustment->currency" /></x-admin.detail-list.item>
            <x-admin.detail-list.item :label="__('admin.booking_adjustments.fields.new_total')"><x-admin.money :minor="$adjustment->new_total_minor" :currency="$adjustment->currency" /></x-admin.detail-list.item>
            <x-admin.detail-list.item :label="__('admin.booking_adjustments.fields.created')" :value="$when($adjustment->created_at)" />
            <x-admin.detail-list.item :label="__('admin.booking_adjustments.fields.expires')" :value="$when($adjustment->expires_at)" />
            <x-admin.detail-list.item :label="__('admin.booking_adjustments.fields.decided_at')" :value="$when($adjustment->decided_at)" />
            <x-admin.detail-list.item :label="__('admin.common.reason')" :value="$adjustment->reason" full />
            @if ($adjustment->decision_note)
                <x-admin.detail-list.item :label="__('admin.booking_adjustments.fields.decision_note')" :value="$adjustment->decision_note" full />
            @endif
            @if ($adjustment->evidence)
                <x-admin.detail-list.item :label="__('admin.booking_adjustments.fields.evidence')" :value="$adjustment->evidence['reference'] ?? ($adjustment->evidence['note'] ?? '—')" full />
            @endif
        </x-admin.detail-list>
    </x-admin.card>

    <x-admin.card :padding="false" class="p-5">
        <h3 class="mb-4 text-sm font-semibold text-slate-800">{{ __('admin.common.activity') }}</h3>
        @include('admin.partials.activity', ['logs' => $logs])
    </x-admin.card>
</x-admin.layouts.app>
