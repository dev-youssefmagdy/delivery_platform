{{-- Lazy tab fragment of the finance pages: an intro line and the tab's own DataTable (initialised by Admin.init after the tab loads). --}}
@if (! empty($intro))
    <p class="mb-4 text-sm text-slate-500">{{ $intro }}</p>
@endif
<x-admin.datatable :table="$dataTable" />
