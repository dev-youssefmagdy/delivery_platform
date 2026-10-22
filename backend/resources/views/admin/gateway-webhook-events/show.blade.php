@php
    $tz = (string) config('admin.display_timezone', 'UTC');
    $when = static fn (?\Carbon\CarbonInterface $at): ?string => $at?->copy()->setTimezone($tz)->translatedFormat('Y-m-d H:i:s');
    // The saved-method token is stored encrypted for reprocessing and never shown.
    $payload = $event->payload;
    if (isset($payload['neutral']['token'])) {
        $payload['neutral']['token'] = '[hidden]';
    }
@endphp
<x-admin.layouts.app :title="__('admin.webhooks.show_title')">
    <x-admin.page-header
        :title="__('admin.webhooks.show_title')"
        :subtitle="$event->event_id"
        :breadcrumbs="[['label' => __('admin.webhooks.title'), 'url' => route('admin.gateway-webhook-events.index')], ['label' => '#'.$event->id]]"
    >
        @can('reprocess', $event)
            <x-admin.confirm-action :url="route('admin.gateway-webhook-events.reprocess', $event)" :title="__('admin.webhooks.confirm.title')" :message="__('admin.webhooks.confirm.message')" :confirm-label="__('admin.webhooks.actions.reprocess')" reason-required variant="warning" icon="refresh">{{ __('admin.webhooks.actions.reprocess') }}</x-admin.confirm-action>
        @endcan
    </x-admin.page-header>

    @if (in_array($event->outcome, [\App\Enums\Payments\WebhookOutcome::Failed, \App\Enums\Payments\WebhookOutcome::InvalidSignature, \App\Enums\Payments\WebhookOutcome::ReplayWindow, \App\Enums\Payments\WebhookOutcome::AmountMismatch], true))
        <x-admin.alert type="danger" class="mb-6" :title="$event->outcome->label()">{{ __('admin.webhooks.notices.'.$event->outcome->value) }}</x-admin.alert>
    @elseif ($event->outcome === \App\Enums\Payments\WebhookOutcome::Unmatched)
        <x-admin.alert type="warning" class="mb-6" :title="$event->outcome->label()">{{ __('admin.webhooks.notices.unmatched') }}</x-admin.alert>
    @endif

    <x-admin.card class="mb-6">
        <x-admin.detail-list>
            <x-admin.detail-list.item :label="__('admin.webhooks.fields.outcome')"><x-admin.badge :value="$event->outcome" /></x-admin.detail-list.item>
            <x-admin.detail-list.item :label="__('admin.webhooks.fields.gateway')" :value="$event->gateway->label()" />
            <x-admin.detail-list.item :label="__('admin.webhooks.fields.kind')" :value="$event->kind?->label()" />
            <x-admin.detail-list.item :label="__('admin.webhooks.fields.signature')" :value="$event->signature_valid ? __('admin.common.yes') : __('admin.common.no')" />
            <x-admin.detail-list.item :label="__('admin.webhooks.fields.event')"><span class="ltr-only break-all">{{ $event->event_id }}</span></x-admin.detail-list.item>
            <x-admin.detail-list.item :label="__('admin.webhooks.fields.reference')"><span class="ltr-only break-all">{{ $event->gateway_reference ?? '—' }}</span></x-admin.detail-list.item>
            <x-admin.detail-list.item :label="__('admin.webhooks.fields.payload_hash')"><span class="ltr-only break-all">{{ $event->payload_hash }}</span></x-admin.detail-list.item>
            <x-admin.detail-list.item :label="__('admin.webhooks.fields.payment')">
                @if ($event->payment)
                    @can('view', $event->payment)
                        <a href="{{ route('admin.payments.show', $event->payment) }}" class="ltr-only text-brand-700 hover:underline">{{ \Illuminate\Support\Str::substr($event->payment->uuid, 0, 8) }}</a>
                    @endcan
                @else
                    —
                @endif
            </x-admin.detail-list.item>
            <x-admin.detail-list.item :label="__('admin.webhooks.fields.attempts')" :value="$event->attempts" />
            <x-admin.detail-list.item :label="__('admin.webhooks.fields.occurred')" :value="$when($event->occurred_at)" />
            <x-admin.detail-list.item :label="__('admin.webhooks.fields.received')" :value="$when($event->received_at)" />
            <x-admin.detail-list.item :label="__('admin.webhooks.fields.processed')" :value="$when($event->processed_at)" />
            <x-admin.detail-list.item :label="__('admin.webhooks.fields.error')" :value="$event->error" />
            @if ($event->reprocess_reason)
                <x-admin.detail-list.item :label="__('admin.webhooks.fields.reprocess_reason')" :value="$event->reprocess_reason" full />
            @endif
        </x-admin.detail-list>
    </x-admin.card>

    @if ($payload !== null)
        <x-admin.card :padding="false" class="p-5">
            <h3 class="mb-4 text-sm font-semibold text-slate-800">{{ __('admin.webhooks.payload') }}</h3>
            <x-admin.json-viewer :data="$payload" />
        </x-admin.card>
    @endif
</x-admin.layouts.app>
