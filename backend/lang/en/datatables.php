<?php

/*
| DataTables 2 language object (passed to the client by <x-admin.datatable>). Placeholders are DataTables'
| own (_START_, _END_, _TOTAL_, _MAX_, _MENU_). emptyTable/zeroRecords are replaced by <x-admin.empty-state>.
*/
return [
    'info' => 'Showing _START_ to _END_ of _TOTAL_ entries',
    'infoEmpty' => 'Showing 0 entries',
    'infoFiltered' => '(filtered from _MAX_ total entries)',
    'lengthMenu' => 'Show _MENU_ entries',
    'loadingRecords' => 'Loading…',
    'processing' => 'Loading…',
    'search' => 'Search:',
    'searchPlaceholder' => 'Search…',
    'emptyTable' => 'No records yet.',
    'zeroRecords' => 'No matching records found.',
    'paginate' => [
        'first' => '«',
        'last' => '»',
        'next' => '›',
        'previous' => '‹',
    ],
    'aria' => [
        'orderable' => 'Order by this column',
        'orderableReverse' => 'Reverse order this column',
        'orderableRemove' => 'Remove ordering',
        'paginate' => [
            'first' => 'First page',
            'last' => 'Last page',
            'next' => 'Next page',
            'previous' => 'Previous page',
            'number' => '',
        ],
    ],
];
