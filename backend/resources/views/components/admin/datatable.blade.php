{{-- Server-side Yajra DataTable (options/language as JSON; initialised by AdminTables). --}}
<div {{ $attributes->class(['admin-datatable']) }} data-table-wrapper="{{ $tableId }}">
    {!! str_replace('<table ', '<table data-options="'.e($options).'" data-language="'.e($language).'" ', $tableHtml) !!}
</div>
