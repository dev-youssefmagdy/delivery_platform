@php
    $tz = (string) config('admin.display_timezone', 'UTC');
    $when = static fn (?\Carbon\CarbonInterface $at): ?string => $at?->copy()->setTimezone($tz)->translatedFormat('Y-m-d H:i:s');
@endphp
<x-admin.layouts.app :title="__('admin.batches.show_title')">
    <x-admin.page-header
        :title="__('admin.batches.show_title').' '.$batch->reference"
        :subtitle="$batch->uuid"
        :breadcrumbs="[['label' => __('admin.batches.title'), 'url' => route('admin.payout-batches.index')], ['label' => $batch->reference]]"
    >
        @can('export', $batch)
            <form method="POST" action="{{ route('admin.payout-batches.export', $batch) }}">
                @csrf
                <x-admin.button type="submit" variant="secondary" icon="download">{{ __('admin.batches.actions.export') }}</x-admin.button>
            </form>
        @endcan
    </x-admin.page-header>

    <x-admin.alert type="info" class="mb-6">{{ __('admin.batches.export_notice') }}</x-admin.alert>

    <x-admin.card class="mb-6">
        <x-admin.detail-list>
            <x-admin.detail-list.item :label="__('admin.batches.fields.status')"><x-admin.badge :value="$batch->status" /></x-admin.detail-list.item>
            <x-admin.detail-list.item :label="__('admin.batches.fields.count')" :value="$batch->payout_count" />
            <x-admin.detail-list.item :label="__('admin.batches.fields.total')"><x-admin.money :minor="$batch->total_minor" :currency="$batch->currency" /></x-admin.detail-list.item>
            <x-admin.detail-list.item :label="__('admin.batches.fields.created_by')" :value="$batch->createdBy?->name" />
            <x-admin.detail-list.item :label="__('admin.batches.fields.created')" :value="$when($batch->created_at)" />
            <x-admin.detail-list.item :label="__('admin.batches.fields.exported_at')" :value="$when($batch->exported_at)" />
            <x-admin.detail-list.item :label="__('admin.batches.fields.completed_at')" :value="$when($batch->completed_at)" />
            <x-admin.detail-list.item :label="__('admin.batches.fields.notes')" :value="$batch->notes" full />
        </x-admin.detail-list>
    </x-admin.card>

    <x-admin.card :padding="false" class="mb-6 p-4">
        <x-admin.datatable :table="$dataTable" />
    </x-admin.card>

    <x-admin.card :padding="false" class="p-5">
        <h3 class="mb-4 text-sm font-semibold text-slate-800">{{ __('admin.common.activity') }}</h3>
        @include('admin.partials.activity', ['logs' => $logs])
    </x-admin.card>
</x-admin.layouts.app>
