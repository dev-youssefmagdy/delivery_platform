@php
    $tz = (string) config('admin.display_timezone', 'UTC');
    $when = static fn (?\Carbon\CarbonInterface $at): ?string => $at?->copy()->setTimezone($tz)->translatedFormat('Y-m-d H:i:s');
    $event = \App\Enums\Notification\NotificationEvent::tryFrom($delivery->event_key);
@endphp
<x-admin.layouts.app :title="__('admin.notification_deliveries.show_title')">
    <x-admin.page-header
        :title="$event?->label() ?? $delivery->event_key"
        :subtitle="$delivery->channel->label()"
        :breadcrumbs="[['label' => __('admin.notification_deliveries.title'), 'url' => route('admin.notification-deliveries.index')], ['label' => '#'.$delivery->id]]"
    >
        @can('retry', $delivery)
            <x-admin.confirm-action :url="route('admin.notification-deliveries.retry', $delivery)" :title="__('admin.notification_deliveries.confirm.title')" :message="__('admin.notification_deliveries.confirm.message')" :confirm-label="__('admin.notification_deliveries.actions.retry')" reason-required variant="warning" icon="refresh">{{ __('admin.notification_deliveries.actions.retry') }}</x-admin.confirm-action>
        @endcan
    </x-admin.page-header>

    @if ($delivery->status === \App\Enums\Notification\DeliveryStatus::Failed)
        <x-admin.alert type="danger" class="mb-6" :title="$delivery->status->label()">{{ __('admin.notification_deliveries.notices.failed') }}</x-admin.alert>
    @elseif ($delivery->status === \App\Enums\Notification\DeliveryStatus::Skipped)
        <x-admin.alert type="warning" class="mb-6" :title="$delivery->status->label()">{{ __('admin.notification_deliveries.skip_reasons.'.($delivery->error_code ?? 'unknown')) }}</x-admin.alert>
    @endif

    <x-admin.card>
        <x-admin.detail-list>
            <x-admin.detail-list.item :label="__('admin.notification_deliveries.fields.status')"><x-admin.badge :value="$delivery->status" /></x-admin.detail-list.item>
            <x-admin.detail-list.item :label="__('admin.notification_deliveries.fields.channel')" :value="$delivery->channel->label()" />
            <x-admin.detail-list.item :label="__('admin.notification_deliveries.fields.event')"><span>{{ $event?->label() }}</span> <span class="ltr-only text-xs text-slate-500">{{ $delivery->event_key }}</span></x-admin.detail-list.item>
            <x-admin.detail-list.item :label="__('admin.notification_deliveries.fields.recipient')"><span>{{ __('admin.notification_deliveries.recipient_'.$delivery->recipient_type) }}</span> <span class="ltr-only">#{{ $delivery->recipient_id }}</span></x-admin.detail-list.item>
            <x-admin.detail-list.item :label="__('admin.notification_deliveries.fields.provider')"><span class="ltr-only">{{ $delivery->provider ?? '—' }}</span></x-admin.detail-list.item>
            <x-admin.detail-list.item :label="__('admin.notification_deliveries.fields.provider_message')"><span class="ltr-only break-all">{{ $delivery->provider_message_id ?? '—' }}</span></x-admin.detail-list.item>
            <x-admin.detail-list.item :label="__('admin.notification_deliveries.fields.attempts')" :value="$delivery->attempts" />
            <x-admin.detail-list.item :label="__('admin.notification_deliveries.fields.error')"><span class="ltr-only">{{ $delivery->error_code ?? '—' }}</span> {{ $delivery->error_message }}</x-admin.detail-list.item>
            <x-admin.detail-list.item :label="__('admin.notification_deliveries.fields.last_attempt')" :value="$when($delivery->last_attempt_at)" />
            <x-admin.detail-list.item :label="__('admin.notification_deliveries.fields.next_attempt')" :value="$when($delivery->next_attempt_at)" />
            <x-admin.detail-list.item :label="__('admin.notification_deliveries.fields.sent')" :value="$when($delivery->sent_at)" />
            <x-admin.detail-list.item :label="__('admin.notification_deliveries.fields.template')" :value="$delivery->template_id === null ? __('admin.notification_deliveries.builtin') : '#'.$delivery->template_id.' v'.$delivery->template_version" />
            <x-admin.detail-list.item :label="__('admin.notification_deliveries.fields.language')" :value="$delivery->notification->locale" />
            <x-admin.detail-list.item :label="__('admin.common.created_at')" :value="$when($delivery->created_at)" />
        </x-admin.detail-list>
    </x-admin.card>

    <x-admin.card class="mt-6" :title="__('admin.notification_deliveries.content')">
        <div class="rounded-xl bg-slate-900 p-4 text-slate-50" lang="{{ $delivery->notification->locale }}" dir="{{ $delivery->notification->locale === 'ar' ? 'rtl' : 'ltr' }}">
            @if ($delivery->title)
                <p class="text-sm font-semibold">{{ $delivery->title }}</p>
            @endif
            <p class="whitespace-pre-line text-sm text-slate-200">{{ $delivery->body }}</p>
        </div>
        <p class="mt-3 text-xs text-slate-500">{{ __('admin.notification_deliveries.content_hint') }}</p>
    </x-admin.card>
</x-admin.layouts.app>
