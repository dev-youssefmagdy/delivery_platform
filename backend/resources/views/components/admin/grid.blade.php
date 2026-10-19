@props(['cols' => 2])
@php
    $columns = match ((int) $cols) {
        1 => 'grid-cols-1',
        3 => 'grid-cols-1 md:grid-cols-2 xl:grid-cols-3',
        4 => 'grid-cols-1 sm:grid-cols-2 xl:grid-cols-4',
        default => 'grid-cols-1 md:grid-cols-2',
    };
@endphp
<div {{ $attributes->class(['grid gap-5', $columns]) }}>{{ $slot }}</div>
