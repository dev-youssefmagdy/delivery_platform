@php
    $tz = (string) config('admin.display_timezone', 'UTC');
    $when = static fn (?\Carbon\CarbonInterface $at): ?string => $at?->copy()->setTimezone($tz)->translatedFormat('Y-m-d H:i:s');
    $currency = app(\App\Support\Money\CurrencyRegistry::class)->get($payment->currency);
    $items = $events->map(static function (\App\Models\PaymentEvent $event) use ($when): array {
        $change = $event->to_status === null ? null : ($event->from_status === null ? $event->to_status->label() : $event->from_status->label().' → '.$event->to_status->label());
        $meta = collect((array) $event->meta)->filter(static fn ($value): bool => is_scalar($value))->map(static fn ($value, $key): string => $key.': '.(is_bool($value) ? ($value ? 'yes' : 'no') : $value))->implode(' · ');

        return [
            'title' => $event->type->label(),
            'badge' => $event->type,
            'time' => $when($event->created_at),
            'actor' => __('admin.payments.sources.'.$event->source),
            'description' => trim(($change ?? '').($change !== null && $meta !== '' ? ' — ' : '').$meta),
        ];
    })->all();
    $key = (string) \Illuminate\Support\Str::uuid();
@endphp
<x-admin.layouts.app :title="__('admin.payments.show_title')">
    <x-admin.page-header
        :title="__('admin.payments.show_title').' '.\Illuminate\Support\Str::substr($payment->uuid, 0, 8)"
        :subtitle="$payment->uuid"
        :breadcrumbs="[['label' => __('admin.payments.title'), 'url' => route('admin.payments.index')], ['label' => \Illuminate\Support\Str::substr($payment->uuid, 0, 8)]]"
    >
        @can('requestRefund', $payment)
            <x-admin.button variant="warning" icon="banknotes" data-modal-open="payment-refund">{{ __('admin.payments.actions.refund') }}</x-admin.button>
        @endcan
        @can('sync', $payment)
            <x-admin.confirm-action :url="route('admin.payments.sync', $payment)" :title="__('admin.payments.confirm.sync_title')" :message="__('admin.payments.confirm.sync_message')" :confirm-label="__('admin.payments.actions.sync')" reason-required variant="primary" icon="refresh">{{ __('admin.payments.actions.sync') }}</x-admin.confirm-action>
        @endcan
    </x-admin.page-header>

    @if ($payment->status === \App\Enums\Payments\PaymentStatus::ReconciliationRequired)
        <x-admin.alert type="danger" class="mb-6" :title="__('admin.payments.notices.review_title')">{{ __('admin.payments.notices.review') }}</x-admin.alert>
    @endif
    @if ($payment->status === \App\Enums\Payments\PaymentStatus::Failed && $payment->failure_code)
        <x-admin.alert type="warning" class="mb-6">{{ __('admin.payments.notices.failed', ['code' => $payment->failure_code]) }}</x-admin.alert>
    @endif

    <x-admin.card :padding="false" class="p-5">
        <x-admin.tabs :tabs="[
            'details' => ['label' => __('admin.common.details'), 'icon' => 'document'],
            'timeline' => ['label' => __('admin.payments.tabs.timeline'), 'icon' => 'clock'],
            'refunds' => ['label' => __('admin.payments.tabs.refunds'), 'icon' => 'banknotes'],
            'activity' => ['label' => __('admin.common.activity'), 'icon' => 'shield'],
        ]">
            <x-admin.tabs.panel id="details">
                <x-admin.detail-list>
                    <x-admin.detail-list.item :label="__('admin.payments.fields.status')"><x-admin.badge :value="$payment->status" /></x-admin.detail-list.item>
                    <x-admin.detail-list.item :label="__('admin.payments.fields.kind')" :value="$payment->kind->label()" />
                    <x-admin.detail-list.item :label="__('admin.payments.fields.amount')"><x-admin.money :minor="$payment->amount_minor" :currency="$payment->currency" /></x-admin.detail-list.item>
                    <x-admin.detail-list.item :label="__('admin.payments.fields.refunded')"><x-admin.money :minor="$payment->refunded_minor" :currency="$payment->currency" /></x-admin.detail-list.item>
                    <x-admin.detail-list.item :label="__('admin.payments.fields.method')" :value="$payment->method_type->label().($payment->paymentMethod?->last4 ? ' •••• '.$payment->paymentMethod->last4 : '')" />
                    <x-admin.detail-list.item :label="__('admin.payments.fields.gateway')" :value="$payment->gateway?->label() ?? __('admin.payments.no_gateway')" />
                    <x-admin.detail-list.item :label="__('admin.payments.fields.reference')"><span class="ltr-only break-all">{{ $payment->gateway_reference ?? '—' }}</span></x-admin.detail-list.item>
                    <x-admin.detail-list.item :label="__('admin.payments.fields.booking')">
                        @can('view', $payment->booking)
                            <a href="{{ route('admin.bookings.show', $payment->booking) }}" class="ltr-only text-brand-700 hover:underline">{{ $payment->booking->code }}</a>
                        @else
                            <span class="ltr-only">{{ $payment->booking->code }}</span>
                        @endcan
                    </x-admin.detail-list.item>
                    <x-admin.detail-list.item :label="__('admin.payments.fields.customer')" :value="$payment->user->name ?? __('admin.customers.unnamed')" />
                    <x-admin.detail-list.item :label="__('admin.payments.fields.attempt')" :value="$payment->attempt" />
                    <x-admin.detail-list.item :label="__('admin.payments.fields.failure_code')" :value="$payment->failure_code" />
                    <x-admin.detail-list.item :label="__('admin.payments.fields.created')" :value="$when($payment->created_at)" />
                    <x-admin.detail-list.item :label="__('admin.payments.fields.paid_at')" :value="$when($payment->paid_at)" />
                    <x-admin.detail-list.item :label="__('admin.payments.fields.expires_at')" :value="$when($payment->expires_at)" />
                    <x-admin.detail-list.item :label="__('admin.payments.fields.settlement')">
                        @if ($payment->settlement_entry_id)
                            @can('viewAny', \App\Models\JournalEntry::class)
                                <a href="{{ route('admin.journal-entries.index', ['filters' => ['search' => 'payment-settlement:'.$payment->uuid]]) }}" class="text-brand-700 hover:underline">{{ __('admin.payments.settlement_posted', ['when' => $when($payment->settled_at)]) }}</a>
                            @else
                                {{ __('admin.payments.settlement_posted', ['when' => $when($payment->settled_at)]) }}
                            @endcan
                        @else
                            —
                        @endif
                    </x-admin.detail-list.item>
                </x-admin.detail-list>
                <x-admin.alert type="info" class="mt-6">{{ __('admin.payments.paid_notice') }}</x-admin.alert>
            </x-admin.tabs.panel>

            <x-admin.tabs.panel id="timeline">
                <x-admin.timeline :items="$items" />
            </x-admin.tabs.panel>

            <x-admin.tabs.panel id="refunds" :url="route('admin.payments.tabs.refunds', $payment)" />
            <x-admin.tabs.panel id="activity" :url="route('admin.payments.tabs.activity', $payment)" />
        </x-admin.tabs>
    </x-admin.card>

    @can('requestRefund', $payment)
        <x-admin.action-modal id="payment-refund" :title="__('admin.payments.refund.title')" :message="__('admin.payments.refund.message', ['max' => app(\App\Support\Money\MoneyFormatter::class)->format(\App\Support\Money\Money::of($refundableMinor, $currency))])" :action="route('admin.payments.refunds.store', $payment)" :submit="__('admin.payments.actions.refund')" variant="warning" icon="banknotes">
            <input type="hidden" name="idempotency_key" value="{{ $key }}">
            <x-admin.form.money name="amount_minor" :label="__('admin.payments.refund.amount')" :value="$refundableMinor" :currency="$payment->currency" required />
        </x-admin.action-modal>
    @endcan
</x-admin.layouts.app>
