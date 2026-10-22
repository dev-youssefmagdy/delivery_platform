@php
    $tz = (string) config('admin.display_timezone', 'UTC');
    $when = static fn (?\Carbon\CarbonInterface $at): ?string => $at?->copy()->setTimezone($tz)->translatedFormat('Y-m-d H:i:s');
    $findings = $run->findings ?? [];
@endphp
<x-admin.layouts.app :title="__('admin.payment_reconciliation.show_title')">
    <x-admin.page-header
        :title="__('admin.payment_reconciliation.show_title')"
        :subtitle="$run->uuid"
        :breadcrumbs="[['label' => __('admin.payment_reconciliation.title'), 'url' => route('admin.payment-reconciliation.index')], ['label' => '#'.$run->id]]"
    />

    <x-admin.card class="mb-6">
        <x-admin.detail-list>
            <x-admin.detail-list.item :label="__('admin.payment_reconciliation.fields.status')"><x-admin.badge :value="$run->status" /></x-admin.detail-list.item>
            <x-admin.detail-list.item :label="__('admin.payment_reconciliation.fields.ran_at')" :value="$when($run->ran_at)" />
            <x-admin.detail-list.item :label="__('admin.payment_reconciliation.fields.window')" :value="$when($run->window_from).' → '.$when($run->window_to)" />
            <x-admin.detail-list.item :label="__('admin.payment_reconciliation.fields.payments')" :value="$run->payments_checked" />
            <x-admin.detail-list.item :label="__('admin.payment_reconciliation.fields.transactions')" :value="$run->transactions_checked" />
            <x-admin.detail-list.item :label="__('admin.payment_reconciliation.fields.timed_out')" :value="$run->timed_out" />
            <x-admin.detail-list.item :label="__('admin.payment_reconciliation.fields.triggered_by')" :value="$run->triggered_by_type === 'admin' ? __('admin.ledger.reconciliation.by_admin') : __('admin.ledger.reconciliation.by_system')" />
        </x-admin.detail-list>
    </x-admin.card>

    <x-admin.card :padding="false" class="p-5">
        <h3 class="mb-4 text-sm font-semibold text-slate-800">{{ __('admin.payment_reconciliation.findings_title') }}</h3>
        @if ($findings === [])
            <x-admin.empty-state icon="check-circle" :title="__('admin.payment_reconciliation.no_findings')" compact />
        @else
            <x-admin.alert type="warning" class="mb-4">{{ __('admin.payment_reconciliation.no_autofix') }}</x-admin.alert>
            <ul class="divide-y divide-slate-100 rounded-(--radius-control) border border-slate-200 text-sm">
                @foreach ($findings as $finding)
                    <li class="px-4 py-3">
                        <p class="font-medium text-danger-700">{{ __('admin.payment_reconciliation.kinds.'.$finding['kind']) }}</p>
                        <p class="mt-1 text-slate-700">
                            @if (! empty($finding['payment_uuid']))
                                @can('viewAny', \App\Models\Payment::class)
                                    <a href="{{ route('admin.payments.show', $finding['payment_uuid']) }}" class="ltr-only text-brand-700 hover:underline">{{ \Illuminate\Support\Str::substr($finding['payment_uuid'], 0, 8) }}</a>
                                @else
                                    <span class="ltr-only">{{ \Illuminate\Support\Str::substr($finding['payment_uuid'], 0, 8) }}</span>
                                @endcan
                            @endif
                            @if (! empty($finding['gateway_reference']))
                                · <span class="ltr-only break-all">{{ $finding['gateway_reference'] }}</span>
                            @endif
                            @if (! empty($finding['local_status']))
                                · {{ __('admin.payment_reconciliation.local') }}: {{ \App\Enums\Payments\PaymentStatus::tryFrom($finding['local_status'])?->label() }}
                            @endif
                            @if (! empty($finding['remote_state']))
                                · {{ __('admin.payment_reconciliation.remote') }}: <span class="ltr-only">{{ $finding['remote_state'] }}</span>
                            @endif
                            @if (isset($finding['local_minor'], $finding['remote_minor']))
                                · {{ __('admin.payment_reconciliation.local') }} <span class="tabular-nums" dir="ltr">{{ $finding['local_minor'] }}</span> / {{ __('admin.payment_reconciliation.remote') }} <span class="tabular-nums" dir="ltr">{{ $finding['remote_minor'] }}</span>
                            @endif
                        </p>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-admin.card>
</x-admin.layouts.app>
