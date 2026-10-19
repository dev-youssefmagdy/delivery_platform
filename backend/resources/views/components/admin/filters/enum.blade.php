@props(['enum', 'except' => []])
{{-- Enum filter (single or `multiple`), options from the enum's labels. --}}
@php
    $excluded = array_map(static fn ($case) => $case instanceof \BackedEnum ? $case->value : $case, (array) $except);
    $options = [];
    foreach ($enum::cases() as $case) {
        if (! in_array($case->value, $excluded, true)) {
            $options[$case->value] = $case instanceof \App\Enums\Contracts\HasLabel ? $case->label() : (string) $case->value;
        }
    }
@endphp
<x-admin.filters.select :options="$options" {{ $attributes }} />
