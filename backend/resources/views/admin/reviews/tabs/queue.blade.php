{{-- Lazy tab fragment of the Reviews page: intro, its own collapsed filters card and the DataTable. --}}
<p class="mb-4 text-sm text-slate-500">{{ $intro }}</p>
<x-admin.filters-card for="{{ $tableId }}">
    <x-admin.filters.select name="statuses" :label="__('reputation.admin.fields.status')" :options="$statuses" />
    <x-admin.filters.date-range name="created" :label="__('admin.common.created_at')" />
</x-admin.filters-card>
<x-admin.datatable :table="$dataTable" />
