@php
    $tz = (string) config('admin.display_timezone', 'UTC');
    $when = static fn (?\Carbon\CarbonInterface $at): ?string => $at?->copy()->setTimezone($tz)->translatedFormat('Y-m-d H:i');
@endphp
<x-admin.layouts.app :title="__('admin.payment_reconciliation.title')">
    <x-admin.page-header
        :title="__('admin.payment_reconciliation.title')"
        :subtitle="__('admin.payment_reconciliation.subtitle')"
        :breadcrumbs="[['label' => __('admin.common.home'), 'url' => route('admin.home')], ['label' => __('admin.payment_reconciliation.title')]]"
    >
        @can('run', \App\Models\PaymentReconciliationRun::class)
            <x-admin.confirm-action :url="route('admin.payment-reconciliation.run')" :title="__('admin.payment_reconciliation.confirm_title')" :message="__('admin.payment_reconciliation.confirm_message')" :confirm-label="__('admin.payment_reconciliation.run')" variant="primary" icon="play">{{ __('admin.payment_reconciliation.run') }}</x-admin.confirm-action>
        @endcan
    </x-admin.page-header>

    @if ($latest === null)
        <x-admin.alert type="warning" class="mb-6">{{ __('admin.payment_reconciliation.never_run') }}</x-admin.alert>
    @elseif ($latest->status === \App\Enums\Finance\ReconciliationStatus::Ok)
        <x-admin.alert type="success" class="mb-6" :title="__('admin.payment_reconciliation.latest_ok')">
            {{ __('admin.payment_reconciliation.latest_summary', ['when' => $when($latest->ran_at), 'payments' => $latest->payments_checked, 'transactions' => $latest->transactions_checked]) }}
        </x-admin.alert>
    @else
        <x-admin.alert type="danger" class="mb-6" :title="__('admin.payment_reconciliation.latest_mismatch')">
            {{ __('admin.payment_reconciliation.latest_summary', ['when' => $when($latest->ran_at), 'payments' => $latest->payments_checked, 'transactions' => $latest->transactions_checked]) }}
            <a href="{{ route('admin.payment-reconciliation.show', $latest) }}" class="font-medium underline">{{ __('admin.payment_reconciliation.see_findings', ['count' => count($latest->findings ?? [])]) }}</a>
        </x-admin.alert>
    @endif

    @if ($eventsNeedingAttention > 0)
        <x-admin.alert type="warning" class="mb-6">
            {{ __('admin.payment_reconciliation.events_attention', ['count' => $eventsNeedingAttention]) }}
            @can('viewAny', \App\Models\GatewayWebhookEvent::class)
                <a href="{{ route('admin.gateway-webhook-events.index', ['filters' => ['needs_attention' => 1]]) }}" class="font-medium underline">{{ __('admin.payment_reconciliation.open') }}</a>
            @endcan
        </x-admin.alert>
    @endif
    @if ($refundsAwaitingApproval > 0)
        <x-admin.alert type="info" class="mb-6">
            {{ __('admin.payment_reconciliation.refunds_waiting', ['count' => $refundsAwaitingApproval]) }}
            @can('viewAny', \App\Models\PaymentRefund::class)
                <a href="{{ route('admin.payment-refunds.index', ['filters' => ['statuses' => ['pending_approval']]]) }}" class="font-medium underline">{{ __('admin.payment_reconciliation.open') }}</a>
            @endcan
        </x-admin.alert>
    @endif

    <x-admin.filters-card for="{{ \App\DataTables\Admin\Payments\PaymentReconciliationRunsDataTable::TABLE_ID }}">
        <x-admin.filters.enum name="status" :label="__('admin.payment_reconciliation.fields.status')" :enum="\App\Enums\Finance\ReconciliationStatus::class" />
    </x-admin.filters-card>

    <x-admin.card :padding="false" class="p-4">
        <x-admin.datatable :table="$dataTable" />
    </x-admin.card>
</x-admin.layouts.app>
