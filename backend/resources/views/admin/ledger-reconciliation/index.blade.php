@php
    $tz = (string) config('admin.display_timezone', 'UTC');
    $when = static fn (?\Carbon\CarbonInterface $at): ?string => $at?->copy()->setTimezone($tz)->translatedFormat('Y-m-d H:i');
@endphp
<x-admin.layouts.app :title="__('admin.ledger.reconciliation.title')">
    <x-admin.page-header
        :title="__('admin.ledger.reconciliation.title')"
        :subtitle="__('admin.ledger.reconciliation.subtitle')"
        :breadcrumbs="[['label' => __('admin.common.home'), 'url' => route('admin.home')], ['label' => __('admin.ledger.reconciliation.title')]]"
    >
        @can('run', \App\Models\LedgerReconciliationRun::class)
            <x-admin.confirm-action :url="route('admin.ledger-reconciliation.run')" :title="__('admin.ledger.reconciliation.confirm_title')" :message="__('admin.ledger.reconciliation.confirm_message')" :confirm-label="__('admin.ledger.reconciliation.run')" variant="primary" icon="play">{{ __('admin.ledger.reconciliation.run') }}</x-admin.confirm-action>
        @endcan
    </x-admin.page-header>

    @if ($latest === null)
        <x-admin.alert type="warning" class="mb-6">{{ __('admin.ledger.reconciliation.never_run') }}</x-admin.alert>
    @elseif ($latest->status === \App\Enums\Finance\ReconciliationStatus::Ok)
        <x-admin.alert type="success" class="mb-6" :title="__('admin.ledger.reconciliation.latest_ok')">
            {{ __('admin.ledger.reconciliation.latest_summary', ['when' => $when($latest->ran_at), 'accounts' => $latest->accounts_checked, 'entries' => $latest->entries_checked]) }}
        </x-admin.alert>
    @else
        <x-admin.alert type="danger" class="mb-6" :title="__('admin.ledger.reconciliation.latest_mismatch')">
            {{ __('admin.ledger.reconciliation.latest_summary', ['when' => $when($latest->ran_at), 'accounts' => $latest->accounts_checked, 'entries' => $latest->entries_checked]) }}
            <a href="{{ route('admin.ledger-reconciliation.show', $latest) }}" class="font-medium underline">{{ __('admin.ledger.reconciliation.see_findings', ['count' => count($latest->findings ?? [])]) }}</a>
        </x-admin.alert>
    @endif

    <x-admin.filters-card for="{{ \App\DataTables\Admin\Finance\ReconciliationRunsDataTable::TABLE_ID }}">
        <x-admin.filters.enum name="status" :label="__('admin.ledger.reconciliation.fields.status')" :enum="\App\Enums\Finance\ReconciliationStatus::class" />
    </x-admin.filters-card>

    <x-admin.card :padding="false" class="p-4">
        <x-admin.datatable :table="$dataTable" />
    </x-admin.card>
</x-admin.layouts.app>
