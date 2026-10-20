@if (! empty($intro))
    <p class="mb-4 text-sm text-slate-500">{{ $intro }}</p>
@endif
<x-admin.datatable :table="$dataTable" />
